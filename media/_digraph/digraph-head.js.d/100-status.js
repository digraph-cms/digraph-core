$(() => {
    var $userBars = $('.digraph-actionbar-user');
    var $actionBars = $('.digraph-actionbar-noun');
    var notifications = document.getElementById('digraph-notifications-container');

    function buildUserActionBars(data) {
        $userBars.html(data);
        $userBars.addClass('active');
    }

    function buildNounActionBar(data, $actionBar) {
        $actionBar.html(data);
        $actionBar.addClass('active');
    }

    function listActionBarNouns($actionBars) {
        if ($actionBars.length == 0) {
            return 'false';
        }
        var out = [];
        $actionBars.each(function () {
            var $b = $(this);
            out.push($b.attr('data-actionbar-noun'));
        });
        return out.join(',');
    }

    function buildNotifications(data) {
        for (var type in data) {
            if (data.hasOwnProperty(type)) {
                for (var i = 0; i < data[type].length; i++) {
                    notifications.innerHTML =
                        '<div class="notification notification-' + type + '">' +
                        data[type][i] +
                        '</div>' +
                        notifications.innerHTML;
                }
            }
        }
    }

    $.get({
        url: digraph.url + "_json/status.json",
        data: {
            actionbars: listActionBarNouns($actionBars),
            notifications: !!notifications,
            useractions: $userBars.length > 0,
        },
        success: function (data) {
            // user actionbars
            if ($userBars.length > 0) {
                buildUserActionBars(data.useractions);
            }
            // noun actionbars
            $actionBars.each(function () {
                var $actionBar = $(this);
                buildNounActionBar(
                    data.actionbars[$actionBar.attr('data-actionbar-noun')],
                    $actionBar
                );
            });
            // notifications
            if (notifications) {
                buildNotifications(data.notifications);
            }
        }
    });

});