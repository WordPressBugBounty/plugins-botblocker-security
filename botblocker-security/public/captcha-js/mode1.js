/* CAPTCHA Mode 1 JS (color button) */

function renderMode1Captcha(params) {
    const { buttons, instruction, colorImageData, colorClass } = params;

    const content = document.getElementById("content");
    content.innerHTML = '';

    const swatch = document.createElement('div');
    swatch.className = colorClass;
    swatch.style.cssText = 'cursor:none;pointer-events:none;background-image:url(data:image/png;base64,' + colorImageData + ');';
    content.appendChild(swatch);

    const p = document.createElement('p');
    p.textContent = instruction;
    content.appendChild(p);

    const wrap = document.createElement('div');
    wrap.style.maxWidth = '200px';
    buttons.forEach(function(btn) {
        const span = document.createElement('span');
        span.className = colorClass;
        span.style.cssText = 'cursor:pointer;display:inline-block;margin:4px;background-image:url(data:image/png;base64,' + btn.image + ');background-size:cover;width:40px;height:40px;';
        span.addEventListener('click', function() {
            window[bbcsJsData.checkFunctionName]('post', window.data, btn.hash);
        });
        wrap.appendChild(span);
    });
    content.appendChild(wrap);
}
 
window.renderMode1Captcha = renderMode1Captcha;