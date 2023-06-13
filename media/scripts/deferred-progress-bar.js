/*
Scripts for displaying deferred job progress bars
*/
(() => {
    document.addEventListener('DigraphDOMReady', prepareBars);
    window.addEventListener('load', prepareBars);
    prepareBars();

    function prepareBars(e) {
        var target = e ? e.target ?? document : document;
        Array.from(target.getElementsByClassName('deferred-progress-bar--nojs'))
            .forEach(bar => {
                bar.classList.add('deferred-progress-bar--waiting');
                bar.classList.remove('deferred-progress-bar--nojs');
                updateBar(bar);
            });
    }

    function updateBar(wrapper) {
        if (wrapper.xhr) wrapper.xhr.abort();
        wrapper.xhr = new XMLHttpRequest();
        var url = Digraph.config.url + '/api/v1/deferred-progress/status.php?group=' + wrapper.dataset.group;
        wrapper.xhr.open('GET', url);
        wrapper.xhr.send();

        wrapper.xhr.addEventListener('load', e => {
            wrapper.classList.remove('deferred-progress-bar--waiting');
            if (e.target.status == 200) {
                var data = JSON.parse(e.target.response);
                if (!data) return barError(wrapper, "No data");
                if (data.pending == 0) return barComplete(wrapper);
                var pct = Math.round(100 * (data.completed / data.total));
                var indicator = wrapper.getElementsByClassName('progress-bar__indicator')[0];
                indicator.style.width = pct + '%';
                setTimeout(() => updateBar(wrapper), 500);
            } else {
                barError(wrapper, 'Error ' + e.target.status);
            }
        });

        wrapper.xhr.addEventListener('error', e => {
            wrapper.classList.remove('deferred-progress-bar--waiting');
            barError(wrapper, 'unknown error');
        });
    }

    function barComplete(wrapper) {
        var bar = wrapper.getElementsByClassName('progress-bar')[0];
        var indicator = wrapper.getElementsByClassName('progress-bar__indicator')[0];
        var text = wrapper.getElementsByClassName('progress-bar__text')[0];
        indicator.style.width = '100%';
        bar.classList.add('progress-bar--safe');
        text.innerText = '';
        if (wrapper.dataset.displayAfter) {
            var after = atob(wrapper.dataset.displayAfter);
            bar.innerHTML += '<div class="progress-bar__after">' + after + '</div>';
        }
        if (wrapper.dataset.bounceAfter) {
            var after = atob(wrapper.dataset.bounceAfter);
            bar.innerHTML += '<div class="progress-bar__after progress-bar__after--redirect"><a href="' + after + '">Click here if you are not redirected automatically in <span class="progress-bar__after--redirect__counter">5</span> seconds</a></div>';
            var counter = bar.getElementsByClassName('progress-bar__after--redirect__counter')[0];
            var interval = setInterval(() => {
                var s = parseInt(counter.innerText) - 1;
                counter.innerText = s;
                if (s == 0) {
                    clearInterval(interval);
                    window.location.href = after;
                }
            }, 1000);
        }
    }

    function barError(wrapper, message) {
        var bar = wrapper.getElementsByClassName('progress-bar')[0];
        var indicator = wrapper.getElementsByClassName('progress-bar__indicator')[0];
        var text = wrapper.getElementsByClassName('progress-bar__text')[0];
        indicator.style.width = '0';
        bar.classList.add('progress-bar--danger');
        text.innerText = message;
    }
})();