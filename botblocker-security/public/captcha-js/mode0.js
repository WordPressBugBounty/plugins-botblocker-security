/* CAPTCHA Mode 0 JS (simple button) */

function renderMode0Captcha(params) {
    const { buttons, styles, confirmText } = params;
    
    let buttonsHtml = '';
    let stylesHtml = '<style>';
    
    buttons.forEach(button => {
        buttonsHtml += button.html;
    });
    
    styles.forEach(style => {
        stylesHtml += style;
    });
    
    stylesHtml += '</style>';
    
    document.getElementById("content").innerHTML = `
        <p>${confirmText}</p>
        ${buttonsHtml}
        ${stylesHtml}
    `;
}

window.renderMode0Captcha = renderMode0Captcha;