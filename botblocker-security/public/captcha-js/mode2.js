/* CAPTCHA Mode 2 JS (image button) */

function renderMode2Captcha(params) {
    const { 
        targetImageData, 
        instruction, 
        buttons, 
        imageRequests,
        ajaxUrl,
        nonce,
        time,
        selectRequestMode
    } = params;
    
    const buttonsHtml = buttons.join('');
    
    document.getElementById("content").innerHTML = `
        <img src="data:image/png;base64,${targetImageData}" />
        <p>${instruction}</p>
        <p style="max-width: 499px;">${buttonsHtml}</p>
    `;

    function fetchAndSetImage(imageParam, elementId) {
        const formData = new FormData();
        formData.append('action', 'bbcs_botblocker_check');
        formData.append('nonce', nonce);
        formData.append('img', imageParam);
        formData.append('time', time);
        formData.append(selectRequestMode, 'img');

        const requestOptions = {
            method: 'POST',
            body: formData
        };

        fetch(ajaxUrl, requestOptions)
            .then(response => response.blob())
            .then(blob => {
                const imageUrl = URL.createObjectURL(blob);
                const img = document.createElement('img');
                img.src = imageUrl;
                const span = document.getElementById(elementId);
                if (span) {
                    span.appendChild(img);
                }
            })
            .catch(error => console.error('Retrieve image error:', error));
    }

    if (Array.isArray(imageRequests)) {
        imageRequests.forEach(req => {
            fetchAndSetImage(req.imageParam, req.elementId);
        });
    }
}

window.renderMode2Captcha = renderMode2Captcha;