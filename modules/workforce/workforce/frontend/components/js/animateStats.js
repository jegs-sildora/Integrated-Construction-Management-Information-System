function animateStats(stats, duration = 800, isCurrency = false) {
    stats.forEach(stat => {
        const { element, value } = stat;
        let range = value - 0;
        if(range === 0) { 
            $(element).text(isCurrency ? "₱" + Number(value).toLocaleString() : value); 
            return; 
        }
        let current = 0;
        let increment = value > 0 ? 1 : -1;
        let stepTime = Math.abs(Math.floor(duration / range));
        const obj = $(element);
        const timer = setInterval(() => {
            current += increment;
            if(current > value) current = value;
            obj.text(isCurrency ? "₱" + Number(current).toLocaleString() : current);
            if(current === value) clearInterval(timer);
        }, stepTime);
    });
}
