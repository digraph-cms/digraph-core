$(() => {
    var $userBars = $('.digraph-actionbar-user');
    var $actionBars = $('.digraph-actionbar-noun');

    function buildUserActionBars(data) {
        $userBars.html(data);
        $userBars.addClass('active');
    }

    function buildNounActionBar(data, $actionBar) {
        $actionBar.html(data);
        $actionBar.addClass('active');
    }

    // user actionbars
    if ($userBars.length > 0) {
        $.get({
            url: digraph.url + "_user/actionbar-user",
            success: buildUserActionBars
        });
    }

    // noun actionbars
    $actionBars.each(function () {
        var $actionBar = $(this);
        $.get({
            url: digraph.url + "_user/actionbar-noun",
            data: {
                'noun': $actionBar.attr('data-actionbar-noun'),
                'verb': $actionBar.attr('data-actionbar-verb')
            },
            success: function (data) {
                buildNounActionBar(data, $actionBar);
            }
        });
    });
});