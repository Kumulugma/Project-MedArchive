$(document).ready(function() {
    // Dashboard specific functionality
    
    // Animate counters
    $('.dashboard-index .h5').each(function() {
        var $this = $(this);
        var countTo = parseInt($this.text());
        
        $({ countNum: 0 }).animate({
            countNum: countTo
        }, {
            duration: 2000,
            easing: 'linear',
            step: function() {
                $this.text(Math.floor(this.countNum));
            },
            complete: function() {
                $this.text(this.countNum);
            }
        });
    });

    // Refresh dashboard data every 5 minutes
    setInterval(function() {
        location.reload();
    }, 300000);
});