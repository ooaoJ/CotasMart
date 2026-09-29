(() => {
    const canvas = document.getElementById('priceChart');
    const data = window.priceHistory || [];
    if (!canvas) return;
    if (data.length < 2) {
        canvas.outerHTML = '<div class="blank-state">Ainda não há histórico suficiente para gerar o gráfico.</div>';
        return;
    }

    const ratio = window.devicePixelRatio || 1;
    const width = canvas.clientWidth;
    const height = 250;
    canvas.width = width * ratio;
    canvas.height = height * ratio;
    const context = canvas.getContext('2d');
    context.scale(ratio, ratio);

    const padding = 38;
    const prices = data.map(item => item.price);
    const minimum = Math.min(...prices);
    const maximum = Math.max(...prices);
    const range = maximum - minimum || 1;

    context.strokeStyle = '#dfe4ea';
    context.lineWidth = 1;
    for (let row = 0; row < 4; row++) {
        const y = 18 + row * ((height - padding - 24) / 3);
        context.beginPath(); context.moveTo(padding, y); context.lineTo(width - 12, y); context.stroke();
    }

    context.strokeStyle = '#167d67';
    context.lineWidth = 3;
    context.beginPath();
    data.forEach((item, index) => {
        const x = padding + index * (width - padding - 18) / (data.length - 1);
        const y = 18 + (maximum - item.price) * (height - padding - 28) / range;
        index ? context.lineTo(x, y) : context.moveTo(x, y);
    });
    context.stroke();

    context.fillStyle = '#667085';
    context.font = '12px Arial';
    context.fillText('R$ ' + maximum.toFixed(2), 0, 14);
    context.fillText('R$ ' + minimum.toFixed(2), 0, height - padding + 4);
})();

