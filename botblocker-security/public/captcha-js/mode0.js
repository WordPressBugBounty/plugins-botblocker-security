/* CAPTCHA Mode 0 JS (simple button) */

function renderMode0Captcha(params) {
    const { buttonHash, buttonClass, confirmText, buttonText } = params;

    const btn = document.createElement('div');
    btn.className = buttonClass;
    btn.style.cursor = 'pointer';
    btn.textContent = buttonText;
    btn.addEventListener('click', function() {
        window[bbcsJsData.checkFunctionName]('post', window.data, buttonHash);
    });

    const content = document.getElementById("content");
    content.innerHTML = '<p>' + confirmText + '</p>';
    content.appendChild(btn);
}

window.renderMode0Captcha = renderMode0Captcha;