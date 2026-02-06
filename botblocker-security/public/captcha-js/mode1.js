/* CAPTCHA Mode 1 JS (color button) */

function renderMode1Captcha(params) {
    const { buttons, instruction, colorImageData, colorImageId } = params;

    const buttonsHtml = buttons.join('');
    
    document.getElementById("content").innerHTML = `
        <div class="s${colorImageId}" style="cursor: none; pointer-events: none; background-image: url(data:image/png;base64,${colorImageData});"></div>
        <p>${instruction}</p>
        <div style="max-width: 200px;">${buttonsHtml}</div>
    `;
}
 
window.renderMode1Captcha = renderMode1Captcha;