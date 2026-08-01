
== Third-Party Libraries ==

BotBlocker Security uses the following open-source libraries (thank you to the authors):

* Bootstrap 5.3.3 - [https://getbootstrap.com/](https://getbootstrap.com/) (bundle includes Popper)
* Popper 1.7.3 - [https://popper.js.org/](https://popper.js.org/)
* DataTables 2.3.3 (Buttons, FixedHeader, Responsive) - [https://datatables.net/](https://datatables.net/)
* JSZip 3.10.1 - [https://github.com/Stuk/jszip](https://github.com/Stuk/jszip)
* jVectorMap 3.1.0 - [https://github.com/alex-pex/jvectormap](https://github.com/alex-pex/jvectormap)
* jVectorMap world.js 3.1.0 - [https://github.com/alex-pex/jvectormap](https://github.com/alex-pex/jvectormap)
* jVectorMap world-merc.js 3.1.0 - [https://github.com/alex-pex/jvectormap](https://github.com/alex-pex/jvectormap)
* Modernizr 2.8.3 - [https://modernizr.com/](https://modernizr.com/)
* MobileDetect 3.74.3 - [https://github.com/serbanghita/Mobile-Detect/tree/3.74.x](https://github.com/serbanghita/Mobile-Detect/tree/3.74.x)
* MobileDetect 4.8.10 - [https://github.com/serbanghita/Mobile-Detect/tree/4.8.x](https://github.com/serbanghita/Mobile-Detect/tree/4.8.x)
* SypexGeo 2.2.3 - [https://github.com/GLOBUS-studio/SypexGeo](https://github.com/GLOBUS-studio/SypexGeo)
* ChartJS 4.5 - [https://www.chartjs.org/](https://www.chartjs.org/)
* Toastify JS 1.12.0 - [https://github.com/apvarun/toastify-js](https://github.com/apvarun/toastify-js)
* Google2FA (pragmarx/google2fa) ^8.0 - [https://github.com/antonioribeiro/google2fa](https://github.com/antonioribeiro/google2fa)
* Constant Time Encoding (paragonie/constant_time_encoding) ^2.6 - [https://github.com/paragonie/constant_time_encoding](https://github.com/paragonie/constant_time_encoding)

== External services ==

This plugin connects to the following external services:

**1. Google reCAPTCHA**

Used to render and validate CAPTCHAs in forms.

* Sends: user's IP address, reCAPTCHA token.
* When: every time a form with reCAPTCHA is loaded and submitted.
* Service: [https://www.google.com/recaptcha/](https://www.google.com/recaptcha/)
* Terms: [https://policies.google.com/terms](https://policies.google.com/terms)
* Privacy Policy: [https://policies.google.com/privacy](https://policies.google.com/privacy)

**2. ip2c.org**

Used to determine the country of a visitor's IP address.

* Sends: the IP address of the visitor.
* When: upon IP check requests.
* Service: [https://ip2c.org/](https://ip2c.org/)
* Privacy Policy: [https://about.ip2c.org/#privacygdpr](https://about.ip2c.org/#privacygdpr)

**3. GLOBUS.studio API (api.globus.studio)**

Used to check IP reputation and bot-related information.

* Sends: the visitor's IP address, request metadata.
* When: when IP verification is triggered.
* Service: [https://globus.studio/](https://globus.studio/)
* Terms: [https://globus.studio/terms-of-use/](https://globus.studio/terms-of-use/)
* Privacy Policy: [https://globus.studio/privacy-policy/](https://globus.studio/privacy-policy/)

**4. BotBlocker API (botblocker.top)**

Used to fetch product and license information.

* Sends: site identifier, plugin version, license key (PRO).
* When: on license check and product update requests.
* Service: [https://botblocker.top/](https://botblocker.top/)
* Terms: [https://botblocker.top/terms-of-service/](https://botblocker.top/terms-of-service/)
* Privacy Policy: [https://botblocker.top/privacy-policy/](https://botblocker.top/privacy-policy/)

**5. Google Tag Manager (if enabled in settings)**

Used for analytics and tag management.

* Sends: standard browser analytics data.
* Service: [https://tagmanager.google.com/](https://tagmanager.google.com/)
* Terms: [https://marketingplatform.google.com/about/analytics/tag-manager/terms/](https://marketingplatform.google.com/about/analytics/tag-manager/terms/)
* Privacy Policy: [https://policies.google.com/privacy](https://policies.google.com/privacy)
