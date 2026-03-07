(function ($) {
    "use strict";
  
    /**
     * Get Font Awesome icon class for browser
     * @param {string} browserName - Browser name
     * @return {string} Font Awesome class
     */

    // debounce / throttle params
    var switchDebounceMs = 200; // minimum interval between switches
    var _lastSwitchTs = 0;

    // Global interception before switching (show.bs.tab) — can be canceled
    $(document).on('show.bs.tab', 'a[data-bs-toggle="tab"]', function(e){
      var now = Date.now();
      if (now - _lastSwitchTs < switchDebounceMs) {
        // Too fast — cancel it
        e.preventDefault();
        return;
      }
      // If any table is currently loading, prevent switching
      var loading = Object.keys(tables).some(function(k){ return !!tables[k].isLoading; });
      if (loading) {
        e.preventDefault();
        // You may briefly show a tooltip or indicator — prevent switching
        // Example: quickly highlight the active tab so the user understands what we’re waiting for
        var activeTab = $('a[data-bs-toggle="tab"].active');
        activeTab && activeTab.addClass('bbcs-tab-wait');
        setTimeout(function(){ activeTab && activeTab.removeClass('bbcs-tab-wait'); }, 400);
        return;
      }
      _lastSwitchTs = now;
    });

    // // Helper: overlay functions assigned to each table
    // function showLoadingOverlay(tableId) {
    //   var $pane = $('#' + tableId).closest('.tab-pane');
    //   if (!$pane.length) return;
    //   if ($pane.find('.bbcs-loading-overlay').length) return;
    //   var overlay = '<div class="bbcs-loading-overlay"><div class="bbcs-spinner"></div></div>';
    //   $pane.append(overlay);
    // }
    // function hideLoadingOverlay(tableId) {
    //   var $pane = $('#' + tableId).closest('.tab-pane');
    //   if (!$pane.length) return;
    //   $pane.find('.bbcs-loading-overlay').remove();
    // }


    function getFABrowserIconClass(browserName) {
        const iconMapping = {
            'Opera': 'fa-brands fa-opera',
            'Microsoft Edge': 'fa-brands fa-edge',
            'Google Chrome': 'fa-brands fa-chrome',
            'Safari': 'fa-brands fa-safari',
            'Mozilla Firefox': 'fa-brands fa-firefox',
            'Internet Explorer': 'fa-brands fa-internet-explorer',
            'Internet Explorer 11': 'fa-brands fa-internet-explorer',
            'Vivaldi': 'fa-solid fa-globe',
            'Brave': 'fa-solid fa-shield-alt',
            'UC Browser': 'fa-solid fa-mobile-alt',
            'Yandex Browser': 'fa-brands fa-yandex-international',
            'Samsung Internet': 'fa-solid fa-mobile',
            'Amazon Silk': 'fa-brands fa-amazon',
            'Naver Whale': 'fa-solid fa-globe',
            'DuckDuckGo Browser': 'fa-solid fa-shield-alt',
            'Kiwi Browser': 'fa-solid fa-globe',
            'Ecosia Browser': 'fa-solid fa-leaf',
            'Huawei Browser': 'fa-solid fa-mobile',
            'Mi Browser': 'fa-solid fa-mobile',
            'Headless Chrome': 'fa-solid fa-robot',
            'Tor Browser': 'fa-solid fa-user-secret',
            'Dolphin Browser': 'fa-solid fa-globe',
            'Puffin Browser': 'fa-solid fa-globe',
            'Maxthon': 'fa-solid fa-globe',
            'Avant Browser': 'fa-solid fa-globe',
            'SeaMonkey': 'fa-solid fa-globe',
            'Konqueror': 'fa-solid fa-globe',
            'Falkon': 'fa-solid fa-globe',
            'Webkit-based browser': 'fa-solid fa-globe',
            'Gecko-based browser': 'fa-solid fa-globe',
            'KHTML-based browser': 'fa-solid fa-globe',
            'NetFront': 'fa-solid fa-globe',
            'iCab': 'fa-solid fa-globe',
            'OmniWeb': 'fa-solid fa-globe',
            'Lynx': 'fa-solid fa-terminal',
            'Links': 'fa-solid fa-link',
            'ELinks': 'fa-solid fa-link',
            'BrowseX': 'fa-solid fa-globe',
            'Epiphany': 'fa-solid fa-globe',
            'K-Meleon': 'fa-solid fa-globe',
            'Midori': 'fa-solid fa-globe',
            'QupZilla': 'fa-solid fa-globe',
            'Otter Browser': 'fa-solid fa-globe',
            'Dooble': 'fa-solid fa-globe',
            'Pale Moon': 'fa-solid fa-globe',
            'Basilisk': 'fa-solid fa-globe',
            'Waterfox': 'fa-solid fa-globe',
            'Comodo Dragon': 'fa-solid fa-globe',
            'Sleipnir': 'fa-solid fa-globe',
            'Lunascape': 'fa-solid fa-globe',
            'QQ Browser': 'fa-brands fa-qq',
            'Sogou Explorer': 'fa-solid fa-globe',
            'Chromium': 'fa-brands fa-chrome',
            'Unknown Browser': 'fa-solid fa-question-circle'
        };

        return iconMapping[browserName] || 'fa-solid fa-question-circle';
    }

    /**
     * Get Font Awesome icon class for OS
     * @param {string} osName - OS name
     * @return {string} Font Awesome class
     */
    function getFAIconClass(osName) {
        const iconMapping = {
            'Windows 10/11': 'fa-brands fa-windows',
            'Windows 10': 'fa-brands fa-windows',
            'Windows 8.1': 'fa-brands fa-windows',
            'Windows 8': 'fa-brands fa-windows',
            'Windows 7': 'fa-brands fa-windows',
            'Windows Vista': 'fa-brands fa-windows',
            'Windows Server 2003/XP x64': 'fa-brands fa-windows',
            'Windows XP': 'fa-brands fa-windows',
            'Windows 2000': 'fa-brands fa-windows',
            'Windows ME': 'fa-brands fa-windows',
            'Windows 98': 'fa-brands fa-windows',
            'Windows 95': 'fa-brands fa-windows',
            'Windows NT 4.0': 'fa-brands fa-windows',
            'Windows 3.11': 'fa-brands fa-windows',
            'Windows': 'fa-brands fa-windows',
            'Windows Phone': 'fa-brands fa-windows',
            
            'Mac OS X': 'fa-brands fa-apple',
            'Mac OS 9': 'fa-brands fa-apple',
            'Mac OS': 'fa-brands fa-apple',
            'iOS (iPhone)': 'fa-brands fa-apple',
            'iOS (iPod)': 'fa-brands fa-apple',
            'iOS (iPad)': 'fa-brands fa-apple',
            
            'Android 15': 'fa-brands fa-android',
            'Android 14': 'fa-brands fa-android',
            'Android 13': 'fa-brands fa-android',
            'Android 12': 'fa-brands fa-android',
            'Android 11': 'fa-brands fa-android',
            'Android 10': 'fa-brands fa-android',
            'Android': 'fa-brands fa-android',
            
            'HarmonyOS': 'fa-solid fa-mobile',
            'Fire OS': 'fa-brands fa-amazon',
            'KaiOS': 'fa-solid fa-mobile-alt',
            'BlackBerry': 'fa-brands fa-blackberry',
            'webOS': 'fa-solid fa-mobile-alt',
            'Chrome OS': 'fa-brands fa-chrome',
            'Tizen': 'fa-solid fa-mobile-alt',
            'Sailfish OS': 'fa-solid fa-mobile-alt',
            'Symbian OS': 'fa-solid fa-mobile-alt',
            
            'Linux': 'fa-brands fa-linux',
            'Ubuntu': 'fa-brands fa-ubuntu',
            'Fedora': 'fa-brands fa-fedora',
            'CentOS': 'fa-brands fa-centos',
            'Red Hat': 'fa-brands fa-redhat',
            'Debian': 'fa-brands fa-linux',
            'Arch Linux': 'fa-brands fa-linux',
            'Manjaro': 'fa-brands fa-linux',
            'Gentoo': 'fa-brands fa-linux',
            'Slackware': 'fa-brands fa-linux',
            'Linux Mint': 'fa-brands fa-linux',
            'elementary OS': 'fa-brands fa-linux',
            'openSUSE': 'fa-brands fa-suse',
            'FreeBSD': 'fa-brands fa-freebsd',
            'OpenBSD': 'fa-brands fa-linux',
            'NetBSD': 'fa-brands fa-linux',
            'Sun Solaris': 'fa-solid fa-sun',
            
            'BeOS': 'fa-solid fa-desktop',
            'Nintendo': 'fa-solid fa-gamepad',
            'PlayStation': 'fa-brands fa-playstation',
            'Xbox': 'fa-brands fa-xbox',
            
            'Unknown OS': 'fa-solid fa-question-circle'
        };
        

        if (!iconMapping[osName]) {
            const osRegex = /^(Windows|Android|Mac OS|iOS|Linux)/i;
            const match = osName.match(osRegex);
            if (match) {
                const baseOS = match[1];
                return iconMapping[baseOS] || 'fa-solid fa-question-circle';
            }
        }

        return iconMapping[osName] || 'fa-solid fa-question-circle';
    }
  
    var tables = {
      "botblocker-hits": { 
        initialized: false, 
        action: "bbcs_get_botblocker_hits",
        currentRequest: null,
        lastRequestId: 0,
        isLoading: false
      },
      "botblocker-hits-admin": {
        initialized: false,
        action: "bbcs_get_botblocker_admin_hits",
        currentRequest: null,
        lastRequestId: 0,
        isLoading: false
      },
      "botblocker-other-admin": {
        initialized: false,
        action: "bbcs_get_botblocker_other_hits",
        currentRequest: null,
        lastRequestId: 0,
        isLoading: false
      },
      "botblocker-hits-full": {
        initialized: false,
        action: "bbcs_get_botblocker_all_hits",
        currentRequest: null,
        lastRequestId: 0,
        isLoading: false
      },
    };
  
    function initializeDataTable(tableId) {
      if (
        !$.fn.DataTable.isDataTable("#" + tableId) &&
        !tables[tableId].initialized
      ) {
        $("#" + tableId).DataTable({
          processing: true,
          serverSide: true,
          scrollX: true,
          fixedHeader: true,
          responsive: true,
          colReorder: true,
          autoWidth: false,
          ajax: {
            url: botblockerData.ajaxurl,
            type: "POST",
            data: function (d) {
              d.action = tables[tableId].action;
              d.nonce = botblockerData.nonce;
              // Add a local request ID to track responses
              d._requestId = ++tables[tableId].lastRequestId;
            },
            beforeSend: function(jqXHR, settings) {
              // Cancel the previous parallel request for this table
              if (tables[tableId].currentRequest && tables[tableId].currentRequest.readyState !== 4) {
                try { tables[tableId].currentRequest.abort(); } catch(e) {}
              }
              tables[tableId].currentRequest = jqXHR;

              // mark downloads + show overlay
              tables[tableId].isLoading = true;
              // showLoadingOverlay(tableId);
            },
            complete: function(jqXHR, textStatus) {
              // clear and hide flags overlay
              tables[tableId].currentRequest = null;
              tables[tableId].isLoading = false;
              // hideLoadingOverlay(tableId);
            },          
            dataSrc: function (json) {
              // ignore old / wrong responses
              if (!json || json.error || (json._requestId && json._requestId !== tables[tableId].lastRequestId)) {
                return []; 
              }
              // serverSide DataTables wait structure: { draw, recordsTotal, recordsFiltered, data }
              if (json.data === undefined && Array.isArray(json)) return json;
              return json.data || [];
            },
          },
          deferRender: true, // It will improve performance with a large number of rows
          // initComplete: function() {
          //   // during initialization, we give the browser a bit of time and then adjust the columns
          //   var api = this.api();
          //   setTimeout(function(){
          //     api.columns.adjust().draw(false);
          //     if (api.responsive) api.responsive.recalc();
          //   }, 25);
          // },
          columns: [ 
            { 
              data: "datetime",
              width: "85px",
              render: function (data, type, row) {
                let addRule_btn = '<a href="#" style="float:right; margin-right:-5px;" class="bbcs-icon-button" data-cid="'+ row.js_info.cid +'"><i style="font-size:14px; line-height:1em; width:14px; height:14px;" class="fas fa-gear bbcs-gray ms-1" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" data-bs-original-title="Add rule"></i></a>';
                return "<span class='bbcs-" + data.m.toLowerCase() + "'>" + data.m +"</span>"+ addRule_btn +"<br><br>" + data.date + "<br><br><small>" + data.time + "</small>";                
              }, 
            },
            { 
              data: "ip_info", 
              width: "100px",
              render: function (data) {
                return "<b>" + data.ip + "</b><br><br><small>"+data.ptr+"</small>";
              },               
            },
            {
              data: "as_info",
              width: "100px",
              render: function (data) {
                return "<b>AS" + data.asnum + "</b><br><br><small>" + data.asname + "</small>";
              },
            },
            { 
              data: "c_info", 
              width: "110px",
              render: function (data) {
                return "<div class='bbcs-td-row'><div class='bbcs-flag-wrapper'><div class='flag flag-"+ data.c.toLowerCase() +" bbcs-flag-scale'></div></div>&nbsp;" + data.c + "</div><br> <small>" + data.cn + "</small><br><br><i class='fa-solid fa-language me-1'></i> <small>" + data.ln + " ("+data.l+")</small>";
              },               
            },
            { 
              data: "u_info", 
              width: "200px",
              render: function (data) {

                const osIconClass = getFAIconClass(data.os);
                const browserIconClass = getFABrowserIconClass(data.br);
                
                return "<div class='bbcs-td-row'><span class='bbcs-device me-1'>"+ data.d +"</span> <small>" + data.os +"</small>&nbsp; <i class='fs-6 me-1 "+osIconClass+"'></i> <i class='fs-6 me-1 "+browserIconClass+"'></i></div>"+ "<br><br> <small>" + data.ua + "</small>";
              },              
             },
            { 
              data: "p_info", 
              width: "300px",
              render: function (data) {
                return "<span class='bbcs-sb'>Page:</span><br>" + data.p + "<br><br><span class='bbcs-sb'>Referer:</span><br><small>" + data.r + "</small>";
              },               
            },
            {
              data: "js_info",
              width: "200px",
              render: function (data) {
                return (
                  "Display ( " + data.js_w +"x" +data.js_h + "x" + data.js_co + "/" + data.js_pi +")<br>" +
                  "Web ( " + data.js_cw +"x" + data.js_ch +")<br>" +
                  "Adblocker: " + data.ad + "<br><br>"+
                  "<small><b>CID:</b> " + data.cid + "</small>"                  
                );
              },
            },
            {
              data: "r_info",
              width: "100px",
              render: function (data) {
                return data.pi +" (Code: <b>"+ data.passed + "</b>) <br><br><small>" + data.result + "</small>";
              },
            },            
          ],
          columnDefs: [
            {
              targets: "_all",
              className: "text-wrap",
            },
          ],
          layout: {
            topStart: {
              buttons: [
                'copy', 'csv', 'excel', 'print', //'colvis',                
                {
                  extend: 'colvis',
                  columnText: function (dt, idx, title) {
                    if ((title || '').trim() === 'Date/Time') {
                      return '';
                    }
                    return title;
                  },                  
                },
                {
                  extend: 'collection',
                  text: 'Length Menu',
                  buttons: [
                    { text: '10', action: function ( e, dt, node, config ) { dt.page.len(10).draw(); } },
                    { text: '25', action: function ( e, dt, node, config ) { dt.page.len(25).draw(); } },
                    { text: '50', action: function ( e, dt, node, config ) { dt.page.len(50).draw(); } },
                    { text: '100', action: function ( e, dt, node, config ) { dt.page.len(100).draw(); } }
                  ]
                }
              ]
            }
          },
          initComplete: function (settings, json) {
            var api = this.api();
            api.columns().every(function () {
              var column = this;
              var header = $(column.header());
              var body = $(column.nodes());
  
              if (body.length > 0) {
                header.css("min-width", body.first().css("width"));
                header.css("max-width", body.first().css("width"));
              }
            });
  
            api.columns.adjust().draw();
          },
        });
        tables[tableId].initialized = true;
      }
    }
  

    $(document).ready(function () {
      function initializeTabTable(target) {

          if (target === '') {
              //initializeDataTable("botblocker-hits");
          } else if (target === '#frontend') {
              initializeDataTable("botblocker-hits");
          } else if (target === '#admin') {
              initializeDataTable("botblocker-hits-admin");
          } else if (target === '#wordpress') {
              initializeDataTable("botblocker-other-admin");
          } else if (target === '#full') {
              initializeDataTable("botblocker-hits-full");
          }
      }
  
      const hash = window.location.hash;
      if (hash) {
          const tabLink = $(`a[href="${hash}"]`);
          if (tabLink.length) {
              tabLink.tab('show'); 
              initializeTabTable(hash); 
          }
      } else {
         // initializeDataTable("botblocker-hits");
      }

      $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
          const target = $(e.target).attr('href'); 
          initializeTabTable(target); 
      });
    });

    $("#botblocker-hits, #botblocker-hits-admin, #botblocker-other-admin, #botblocker-hits-full").on("click", 'td>a.bbcs-icon-button', function () {
    
      $("#AddRuleModal").modal("show");
      
      $('#type').val('ip'); 
      $('#rule').find('option').not('[value="allow"], [value="block"]').hide();

  //    const ruleOptions = $('#rule').html();

      const cid = $(this).attr("data-cid").trim();
      const form = document.getElementById("addRuleForm");
      if(!cid) {
        console.error("CID not found");
        return;
      }

      $.ajax({
        url: botblockerData.ajaxurl,
        type: "POST",
        data: {
          action: "bbcs_get_botblocker_hits_data_for_modal",
          cid: cid,
          nonce: botblockerData.nonce,
        },
        success: function (response) {
          if (response.success) {
            var data = response.data;
            add_modal_selected_value(data);
          } else {
            alert("Failed to get response: " + response.data);
          }
        },
      });
      

      function add_modal_selected_value(data) {
        const selectedData = form.querySelector("#data");
        selectedData.value = data[0].ip;
        $('#this_ip').val(data[0].ip);

        const selectedType = form.querySelector("#type");
        let selectedTypeValue = selectedType.options[selectedType.selectedIndex].value;
      
        $('#type').on('change', function () {
          selectedTypeValue = $(this).val();

          switch (selectedTypeValue) {
            case 'ip':
              selectedData.value = data[0].ip;
              $('#rule').find('option').not('[value="allow"], [value="block"]').hide();
              break;
            case 'useragent': 
              $('#rule').find('option').show();
              selectedData.value = data[0].useragent;
              break;
            case 'ptr':
              $('#rule').find('option').show();
                selectedData.value = data[0].ptr;
                break;
            case 'referer':
              $('#rule').find('option').show();
              selectedData.value = data[0].referer;
              break;
            case 'country':
              $('#rule').find('option').show();
              selectedData.value = data[0].country_name;
              break;
            case 'asname':
              $('#rule').find('option').show();
              selectedData.value = data[0].asname;
              break;
            case 'asnum':
              $('#rule').find('option').show();
              selectedData.value = data[0].asnum;
              break;
            case 'lang':
              $('#rule').find('option').show();
              selectedData.value = data[0].name_lang;
              break;
            default:
             // console.log('None of the options matched');
              break;
          }

        });
      }

      const expiresInput = form.querySelector("#expires");      
      const now = new Date();
      now.setDate(now.getDate() + 1);
      const year = now.getFullYear();
      const month = String(now.getMonth() + 1).padStart(2, "0");
      const day = String(now.getDate()).padStart(2, "0");
      const hours = String(now.getHours()).padStart(2, "0");
      const minutes = String(now.getMinutes()).padStart(2, "0");

      const formattedDate = `${year}-${month}-${day}T${hours}:${minutes}`;
      expiresInput.value = formattedDate;
    });

    $("#addRuleForm").on("submit", function (e) {
        e.preventDefault();        
        $.ajax({
            url: botblockerData.ajaxurl,
            type: "POST",
            data:
                $(this).serialize() +
                "&action=bbcs_hit_to_rule&nonce=" +
                botblockerData.nonce,
            success: function (response) {
                if (response.success) {

                    $('#this_ip').val('');
                    $("#AddRuleModal").modal("hide");                    
                } else {
                    alert("Failed to create rule: " + response.data);
                }
            },
        });
    });
 
  // Re-initialize tooltips after AJAX completes
  $(document).ajaxComplete(function() {
    $('[data-bs-toggle="tooltip"]').tooltip(); // Bootstrap 4
    // Bootstrap 5:
    // document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
  });

  })(jQuery);