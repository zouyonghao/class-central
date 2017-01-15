(function($){
    var FollowButton = function(el) {
        var self = this;

        self._state = {};

        self.state({
            el: el,
            action: el.find('[data-action]').html(),
            count: parseInt(el.find('[data-count]').html().replace(/\,/, ''), 10),
            loading: false,
            isFollowing: el.hasClass('is-following'),
            data: el.data('follow')
        });

        setTimeout(function() {
            self.bindClick();
            self.bindHover();
        }, 0);
    };

    FollowButton.prototype.bindClick = function() {
        var self = this;

        self.state().el.on('click', function() {
            self.state({
                loading: true
            });

            if (self.state().isFollowing) {
                $(document).trigger('followButton:unfollow', [self.state().data, function(num) {
                    num = typeof(num !== 'undefined') ? parseInt(num, 10) : false;
                    self.state({
                        action: 'Follow',
                        isFollowing: false,
                        count: num ? num : self.state().count - 1,
                        loading: false
                    });
                }]);
            }
            else {
                $(document).trigger('followButton:follow', [self.state().data, function(num) {
                    num = typeof(num !== 'undefined') ? parseInt(num, 10) : false;
                    self.state({
                        action: 'Following',
                        isFollowing: true,
                        count: num ? num : self.state().count + 1,
                        loading: false
                    });
                }]);
            }
        });
    };

    FollowButton.prototype.bindHover = function() {
        var self = this;

        self.state().el.on('mouseenter', function() {
            if (self.state().isFollowing) {
                self.state({
                    action: 'Unfollow'
                });
            }
        });

        self.state().el.on('mouseleave', function() {
            if (self.state().isFollowing) {
                self.state({
                    action: 'Following'
                });
            } else {
                self.state({
                    action: 'Follow'
                });
            }
        });
    };

    FollowButton.prototype.formatNumber = function(num) {
        var str = num + '';
        var nums = {
            longHand: num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")
        };

        if (num < 1000) {
            nums.shortHand = str;
        }

        if (num < 10000) {
            nums.shortHand = str.charAt(0) + ',' + str.substring(1);
        }

        if (num < 1000000) {
            nums.shortHand = (num / 1000).toFixed(num % 1000 !== 0) + 'k';
        } else {
            nums.shortHand = (num / 1000000).toFixed(num % 1000000 !== 0) + 'M';
        }

        return nums;
    };

    FollowButton.prototype.state = function(state) {
        var self = this;

        if (state) {
            $.each(state, function(key, value) {
                self._state[key] = value;

                switch (key) {
                    case 'isFollowing':
                        if (value) {
                            self.state().el.addClass('is-following');
                        } else {
                            self.state().el.removeClass('is-following');
                        }
                        break;
                    case 'action':
                        self.state().el.find('[data-action]').html(value);
                        break;
                    case 'count':
                        var numbers = self.formatNumber(value);
                        self.state().el.find('[data-count]').html(numbers.longHand);
                        self.state().el.find('[data-display-count]').html(numbers.shortHand);
                        if (value > 1000000 && state.isFollowing) {
                            self.state().el.find('[data-learners]').hide();
                        } else {
                            self.state().el.find('[data-learners]').show();
                        }
                        break;
                    case 'loading':
                        if (value) {
                            self.state().el.addClass('is-loading');
                        } else {
                            self.state().el.removeClass('is-loading');
                        }
                        break;
                }
            });
        } else {
            return this._state;
        }
    };

    $('.btn-follow').each(function() {
        new FollowButton($(this));
    });
})(jQuery);
