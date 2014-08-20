var CC = CC || {
    Class : {}
}

CC.Class['Utilities'] = (function(){

    /**
     *  Sends a popup notification to the user
     * @param title
     * @param text
     * @param type
     */
    function notify(title,text,type) {
        new PNotify({
            'title': title,
            'text' : text,
            'type' : type,
            'animation' : 'show'
        });
    }

    /**
     * Sends a popup notification and shows it
     * for a specific amount of time
     * @param title
     * @param text
     * @param type
     * @param delay
     */
    function notifyWithDelay( title, text, type, delay) {
        new PNotify({
            title: title,
            text: text,
            type: type,
            animation: 'show',
            delay: delay * 1000
        });
    }

    /**
     * Checks whether a string is empty
     * @param str
     * @returns {boolean}
     */
    function isEmpty(str) {
        return (!str || 0 === str.length);
    }

    return {
        notify: notify,
        notifyWithDelay: notifyWithDelay,
        isEmpty:isEmpty
    };
})();

CC.Class['Profile'].init('#save-profile');