/* Main CAPTCHA JS Controller */

function renderCaptcha() {
  if (typeof bbcsCaptchaData === "undefined") {
    console.error("BBCS CAPTCHA initial data not loaded");
    return;
  }

  const mode = parseInt(bbcsCaptchaData.mode, 10);
  const params = bbcsCaptchaData.params;

  switch (mode) {
    case 0:
      if (typeof renderMode0Captcha === "function") {
        renderMode0Captcha(params);
      }
      break;
    case 1:
      if (typeof renderMode1Captcha === "function") {
        renderMode1Captcha(params);
      }
      break;
    case 2:
      if (typeof renderMode2Captcha === "function") {
        renderMode2Captcha(params);
      }
      break;
    case 3:
      if (typeof renderMode3Captcha === "function") {
        renderMode3Captcha(params);
      }
      break;
    case 4:
      if (typeof renderMode4Captcha === "function") {
        renderMode4Captcha(params);
      }
      break;
    case 5:
      if (typeof renderMode5Captcha === "function") {
        renderMode5Captcha(params);
      }
      break;
    case 6:
      if (typeof renderMode6Captcha === "function") {
        renderMode6Captcha(params);
      }
      break;
    case 7:
      if (typeof renderMode7Captcha === "function") {
        renderMode7Captcha(params);
      }
      break;
    default:
      console.error("Unknown CAPTCHA mode:", mode);
      break;
  }
}

window.renderCaptcha = renderCaptcha;