function renderMode2Captcha(params) {
    var targetImageData = params.targetImageData;
    var instruction = params.instruction;

    var content = document.getElementById("content");
    content.innerHTML = "";

    var img = document.createElement("img");
    img.src = "data:image/png;base64," + targetImageData;
    content.appendChild(img);

    var p = document.createElement("p");
    p.textContent = instruction;
    content.appendChild(p);

    if (params.buttonImages) {
        /* Inline base64 mode: all images are embedded directly in captcha data. */
        var buttonImages = params.buttonImages;
        var row = document.createElement("p");
        row.style.maxWidth = "500px";

        for (var i = 0; i < buttonImages.length; i++) {
            (function(item) {
                var span = document.createElement("span");
                span.id = item.id;
                span.style.cursor = "pointer";

                var btnImg = document.createElement("img");
                btnImg.src = "data:image/jpeg;base64," + item.imageData;
                span.appendChild(btnImg);

                span.addEventListener("click", function() {
                    window[bbcsJsData.checkFunctionName]("post", window.data, item.clickHash);
                });

                row.appendChild(span);
            })(buttonImages[i]);
        }

        content.appendChild(row);

    } else if (params.imageRequests) {
        /* Legacy mode: images loaded via separate AJAX requests. */
        var buttons = params.buttons;
        var imageRequests = params.imageRequests;
        var ajaxUrl = params.ajaxUrl;
        var nonce = params.nonce;
        var time = params.time;
        var selectRequestMode = params.selectRequestMode;

        var rowEl = document.createElement("p");
        rowEl.style.maxWidth = "500px";
        rowEl.innerHTML = buttons.join("");
        content.appendChild(rowEl);

        function fetchAndSetImage(imageParam, elementId) {
            var formData = new FormData();
            formData.append("action", "bbcs_botblocker_check");
            formData.append("nonce", nonce);
            formData.append("img", imageParam);
            formData.append("time", time);
            formData.append(selectRequestMode, "img");

            fetch(ajaxUrl, { method: "POST", body: formData })
                .then(function(response) {
                    if (!response.ok) {
                        throw new Error("HTTP " + response.status);
                    }
                    return response.blob();
                })
                .then(function(blob) {
                    var imageUrl = URL.createObjectURL(blob);
                    var imgEl = document.createElement("img");
                    imgEl.src = imageUrl;
                    var span = document.getElementById(elementId);
                    if (span) {
                        span.appendChild(imgEl);
                    }
                })
                .catch(function(error) {
                    console.error('[BBCS DEBUG] Retrieve image error:', error);
                });
        }

        for (var j = 0; j < imageRequests.length; j++) {
            fetchAndSetImage(imageRequests[j].imageParam, imageRequests[j].elementId);
        }
    }
}

window.renderMode2Captcha = renderMode2Captcha;