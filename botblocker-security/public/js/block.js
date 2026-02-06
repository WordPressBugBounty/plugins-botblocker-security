// BotBlocker Block JavaScript

var data = window.bbcsBlockData;
var infos = document.querySelectorAll(".container .info");
var target = infos[infos.length - 1];

target.textContent = "";

if (data.hasCountdown && data.waitSeconds > 0) {
  var wait = parseInt(data.waitSeconds, 10) || 0;
  var wrapper = document.createElement("div");
  wrapper.innerHTML =
    "<center><h1 class='info info-block'>" +
    String(data.accessBlocked) +
    "</h1>" +
    "<h5 class='block-string'>" +
    String(data.secondsLeft) +
    ' <span id="countdownTimer"><b>' +
    wait +
    "</b></span></h5></center>";
  target.appendChild(wrapper);

  var endTime = Date.now() + wait * 1000;

  function updateCounter() {
    var timeLeft = Math.ceil((endTime - Date.now()) / 1000);
    var t = document.getElementById("countdownTimer");

    if (t) {
      var b = t.querySelector("b");
      if (b) {
        b.textContent = timeLeft > 0 ? timeLeft : 0;
      } else {
        t.innerHTML = "<b>" + (timeLeft > 0 ? timeLeft : 0) + "</b>";
      }
    }

    if (timeLeft <= 0) {
      location.reload();
      return;
    }

    requestAnimationFrame(updateCounter);
  }

  requestAnimationFrame(updateCounter);
}

if (data.reasonView && data.reasonText) {
  target.appendChild(document.createElement("br"));
  target.appendChild(document.createTextNode(String(data.reasonText)));
}