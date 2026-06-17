/* CAPTCHA Mode 1 JS (color button) */

function renderMode1Captcha(params) {
    const { buttons, instruction, colorImageData, colorClass } = params;

    if (typeof bbcsJsData !== 'undefined' && bbcsJsData.debugEnabled) {
        console.log('[BBCS DEBUG] renderMode1Captcha: buttons=' + (buttons ? buttons.length : 0) + ' colorImageData_len=' + (colorImageData ? colorImageData.length : 0) + ' colorClass=' + colorClass);
    }

    const content = document.getElementById("content");
    if (!content) {
        console.error('[BBCS DEBUG] renderMode1Captcha: #content element not found');
        return;
    }
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
        const el = document.createElement('span');
        el.className = colorClass;
        el.style.cssText = 'background-image:url(data:image/png;base64,' + btn.image + ');';
        el.addEventListener('click', function() {
            window[bbcsJsData.checkFunctionName]('post', window.data, btn.hash);
        });
        wrap.appendChild(el);
    });
    content.appendChild(wrap);
}
 
window.renderMode1Captcha = renderMode1Captcha;
