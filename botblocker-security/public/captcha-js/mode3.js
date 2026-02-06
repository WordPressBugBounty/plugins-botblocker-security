/* CAPTCHA Mode 3 JS (recaptcha + button) */

function renderMode3Captcha(params) {
    const { buttons, styles, confirmText, loadingText, recaptchaKey } = params;
    
    var script = document.createElement("script");
    script.src = "https://www.google.com/recaptcha/api.js";
    document.body.appendChild(script);

    script.onload = function() {
        document.getElementById("content").innerHTML = `
            <div style="max-width: 302px; text-align: center;margin: 0 auto;">
                <p>${confirmText}</p>
                <p class="g-recaptcha" style="display: inline-block;" data-sitekey="${recaptchaKey}" data-callback="onRecaptchaSuccess">${loadingText}</p>
            </div>
        `;
    };

    window.onRecaptchaSuccess = function(token) {
        window.data += "&g-recaptcha-response=" + token;

        let buttonsHtml = '';
        buttons.forEach(button => {
            buttonsHtml += button.html;
        });

        let stylesHtml = '<style>';
        styles.forEach(style => {
            stylesHtml += style;
        });
        stylesHtml += '</style>';

        document.getElementById("content").innerHTML = `
            <div style="max-width: 302px; text-align: center;margin: 0 auto;">
                ${buttonsHtml}
            </div>
            ${stylesHtml}
        `;
    };
}

window.renderMode3Captcha = renderMode3Captcha;