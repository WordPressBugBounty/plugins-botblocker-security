/* Main CAPTCHA JS Controller */

function renderCaptcha() {
  if (typeof bbcsCaptchaData === "undefined") {
    console.error('[BBCS DEBUG] CAPTCHA initial data not loaded');
    if (typeof bbcsJsData !== 'undefined' && bbcsJsData.debugEnabled) {
      console.log('[BBCS DEBUG] renderCaptcha: bbcsCaptchaData is undefined');
    }
    return;
  }

  const mode = parseInt(bbcsCaptchaData.mode, 10);
  const params = bbcsCaptchaData.params;

  if (typeof bbcsJsData !== 'undefined' && bbcsJsData.debugEnabled) {
    console.log('[BBCS DEBUG] renderCaptcha: mode=' + mode + ' params keys=' + (params ? Object.keys(params).join(',') : 'null') + ' token=' + (bbcsCaptchaData.challengeToken ? bbcsCaptchaData.challengeToken.substring(0,10)+'...' : 'empty'));
  }

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
    case 8:
      if (typeof renderMode8Captcha === "function") {
        renderMode8Captcha(params);
      }
      break;
    default:
      console.error('[BBCS DEBUG] Unknown CAPTCHA mode:', mode);
      break;
  }
}

window.renderCaptcha = renderCaptcha;