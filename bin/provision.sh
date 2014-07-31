#!/bin/bash -e

## Must be run as root...
if [[ $EUID -ne 0 ]]; then
   echo "This script must be run as root" 1>&2
   exit 1
fi

## Define some local functions...
__echo () {
    echo -e "\n[ ClassCentral ] $1"
}

__create_profile () {
    cat << 'EOF' > $1
# set PATH so it includes user's private bin if it exists
if [ -d "$HOME/bin" ] ; then
    PATH="$HOME/bin:$PATH"
fi

if [ -f /etc/bash_completion ]; then
    . /etc/bash_completion
fi

cd /vagrant

EOF

    chmod 0644 $1
    return 0
}

__create_vhost () {
    cat << 'EOF' > $1
<VirtualHost *:80>
    ServerAdmin webmaster@localhost

    DocumentRoot /vagrant/web
    <Directory />
        Options FollowSymLinks
        AllowOverride None
    </Directory>
    <Directory /vagrant/web/>
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        Order allow,deny
        Allow from all
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/classcentral-error.log

    # Possible values include: debug, info, notice, warn, error, crit, alert, emerg.
    LogLevel warn

    CustomLog ${APACHE_LOG_DIR}/classcentral-access.log combined
</VirtualHost>
EOF

    chmod 0644 $1
    return 0
}

## And now, the meat of the script...

# Explicitly work out of Vagrant home
PROVISION_BASE=/home/vagrant
cd $PROVISION_BASE

# To run apt-get installs unattended
export DEBIAN_FRONTEND=noninteractive

__echo "Ensure apt repositories are fresh"
apt-get -q update

__echo "Installing some base packages with apt"
apt-get -q -y install build-essential autoconf re2c git-core curl moreutils

__echo "Override root's bash profile"
__create_profile /root/.profile

__echo "Override vagrant's bash profile"
__create_profile $PROVISION_BASE/.profile
chown vagrant:vagrant $PROVISION_BASE/.profile

__echo "Installing LAMP Server stack"
apt-get -q -y install lamp-server^ php5-cli php5-dev php5-curl php5-intl

if [[ ! -e /etc/apache2/envvars.backup ]]; then
    __echo "Updating APACHE_RUN_(USER|GROUP) in /etc/apache2/envvars"
    cp /etc/apache2/envvars /etc/apache2/envvars.backup
    sed -i 's/^\(export APACHE_RUN_[A-Z]\{4,5\}=\)www-data$/\1vagrant/g' /etc/apache2/envvars
fi

if [[ ! -e /etc/mysql/my.cnf.backup ]]; then
    __echo "Adding root@33.33.33.1 to mysql.user table"
    /usr/bin/mysql -u root << 'EOF'
USE mysql;
CREATE TEMPORARY TABLE user_dupe SELECT * from mysql.user WHERE User='root' LIMIT 1;
UPDATE user_dupe SET Host='33.33.33.1' WHERE User='root';
INSERT INTO mysql.user SELECT * FROM user_dupe;
DROP TABLE user_dupe;
EOF

    __echo "Updating default values in my.cnf"
    cp /etc/mysql/my.cnf /etc/mysql/my.cnf.backup
    sed -i 's/^bind-address[[:blank:]]*=[[:blank:]]*127.0.0.1$/bind-address = 0.0.0.0/g' /etc/mysql/my.cnf
fi

if [[ ! -e /etc/php5/apache2/php.ini.backup ]]; then
    __echo "Updating default values in php.ini"
    cp /etc/php5/apache2/php.ini /etc/php5/apache2/php.ini.backup
    sed -i 's/^short_open_tag[[:blank:]]*=[[:blank:]]*On$/short_open_tag = Off/g' /etc/php5/apache2/php.ini
    sed -i 's/^memory_limit[[:blank:]]*=[[:blank:]]*[0-9]*M$/memory_limit = -1/g' /etc/php5/apache2/php.ini
    sed -i 's/^display_errors[[:blank:]]*=[[:blank:]]*Off$/display_errors = On/g' /etc/php5/apache2/php.ini
    sed -i 's/^display_startup_errors[[:blank:]]*=[[:blank:]]*Off$/display_startup_errors = On/g' /etc/php5/apache2/php.ini
    sed -i 's/^track_errors[[:blank:]]*=[[:blank:]]*Off$/track_errors = On/g' /etc/php5/apache2/php.ini
    sed -i 's/^;date.timezone[[:blank:]]*=[[:blank:]]*$/date.timezone = "America\/Chicago"/g' /etc/php5/apache2/php.ini
    sed -i 's/^;sendmail_path[[:blank:]]*=[[:blank:]]*$/sendmail_path = \/usr\/lib\/sendmail -t -i/g' /etc/php5/apache2/php.ini
    rm -f /vagrant/app/logs/php_errors.log
    sed -i 's/^;error_log[[:blank:]]*=[[:blank:]]*syslog$/error_log = \/vagrant\/app\/logs\/php_errors.log/g' /etc/php5/apache2/php.ini
fi

if [[ -e /etc/apache2/sites-enabled/000-default ]]; then
    __echo "Disable default apache site"
    a2dissite 000-default
fi

if [[ ! -e /etc/apache2/sites-enabled/000-classcentral ]]; then
    __echo "Create and enable ClassCentral vhost"
    __create_vhost /etc/apache2/sites-available/000-classcentral
    a2ensite 000-classcentral
    a2enmod rewrite
fi

# this supresses "Could not reliably determine the server's fully qualified domain name" on apache restart
FQDNINSTALLED=$(grep -i "localhost.localdomain" /etc/hosts | wc -l)
if [[ "$FQDNINSTALLED" -lt "1" ]]; then
    __echo "Set fqdn in /etc/hosts (to supress apache warnings)"
    sed -i 's/^\(127\.0\.0\.1[[:blank:]]*\)\(localhost\)$/\1localhost.localdomain \2/g' /etc/hosts
    sed -i 's/^\(127\.0\.1\.1[[:blank:]]*\)\(classcentral.*\)$/\1classcentral.dev \2/g' /etc/hosts
fi

__echo "Restart apache after vhost, module, and php changes"
apache2ctl restart

if [[ -e /vagrant/app/config/parameters.yml ]]; then
    CC_DB_NAME=$(perl -ne 'print $1 if m/\s+database_name\s*:\s*([\S]+)\s+/m;' /vagrant/app/config/parameters.yml)

    __echo "Install Vendors"
    (
        cd /vagrant
        curl -sS https://getcomposer.org/installer | php
        /vagrant/composer.phar install
    )

    if [[ -z "${CC_DB_NAME}" ]]; then
        __echo "Cannot Load & Migrate Database: no 'database_name' found in app/config/parameters.yml"
    else
        __echo "Load & Migrate Database '${CC_DB_NAME}'"
        (
            cd /vagrant
            mysql -u root -e "CREATE DATABASE ${CC_DB_NAME}"
            mysql -u root -D ${CC_DB_NAME} < /vagrant/extras/cc_db.sql
            chmod +x /vagrant/app/console
            php /vagrant/app/console doctrine:migrations:migrate --no-interaction
        )
    fi
else
    __echo "Cannot Load & Migrate Database: no app/config/parameters.yml found"
fi

__echo "Shell provisioning complete!"
