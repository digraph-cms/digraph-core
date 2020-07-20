/* load cron in a worker */
setTimeout(()=>{
    if (window.Worker) {
        new Worker(digraph.url+'_cron/');
    }
},5000);
