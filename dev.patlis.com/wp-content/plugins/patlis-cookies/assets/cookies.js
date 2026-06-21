let allCookies = false;
let preferencesCookies = false;
let statisticsCookies = false;
let marketingCookies = false;
// check if cookie "patlis-cookie" exist
const cookie = document.cookie.split('; ').find(row => row.startsWith('patlis-cookie='));
let $loadBasicModal = false;
//set case
let $case = 0;
let userClick = false;

// 1 page load. set case
var urlParams = new URLSearchParams(window.location.search);
if (urlParams.has('speedtest')) {
    //Test mode: all cookies accepted via URL parameter
    preferencesCookies = true; statisticsCookies = true; marketingCookies = true; allCookies = true;
    $case = 1;
} else if (cookie) {
    try {
        var cookieValue = JSON.parse(cookie.split('=')[1]);
        preferencesCookies = cookieValue.preferences;
        statisticsCookies = cookieValue.statistics;
        marketingCookies = cookieValue.marketing;
        allCookies = cookieValue.all;

        if (allCookies === true) { 
            //Case 1. Cookie exist & accept all
            $case = 1;
        }else{
            if(preferencesCookies == true || statisticsCookies == true || marketingCookies == true) {
                //Case 3: Cookie exist & accept some
                $case = 3;
            }else{
                //Case 5: Cookie exist & accept only necessary
                $case = 5;// it is same like case 2
            }
        }
    } catch (e) { console.log(e);}
}else{
    //case 2: Cookie not exist
    $case = 2;
    $loadBasicModal= true; 
}

//-------------------------gtm start--------------------------------
  // Define dataLayer and the gtag function.
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}

  // Set default consent to 'denied' as a placeholder
  gtag('consent', 'default', {
    'ad_personalization': 'denied',
    'ad_storage': 'denied',
    'ad_user_data': 'denied',
    'analytics_storage': 'denied',
    'functionality_storage': 'denied',
    'personalization_storage': 'denied',
    'security_storage': 'granted'
  });


// Retrieve saved cookies and update the dataLayer
if (cookie) {
    updateConsentDataLayer();
}

// Update consent dataLayer when user changes cookie settings
function updateConsentDataLayer() {
    gtag('consent', 'update', 
    {
        'ad_personalization': marketingCookies ? 'granted' : 'denied',
        'ad_storage': marketingCookies ? 'granted' : 'denied',
        'ad_user_data': marketingCookies ? 'granted' : 'denied',
        'analytics_storage': statisticsCookies ? 'granted' : 'denied',
        'functionality_storage': preferencesCookies ? 'granted' : 'denied',
        'personalization_storage': preferencesCookies ? 'granted' : 'denied',
        'security_storage': 'granted'
    });
}
//-------------------------gtm end--------------------------------

document.addEventListener("DOMContentLoaded", function() {
    if ($loadBasicModal) { showBasicModal();}

    document.querySelectorAll('a[href="#cookies"]').forEach(function(element) {
        element.addEventListener('click', function(event) {
            userClick = true;
            event.preventDefault();
            getCookieSettings();
        });
    });
});

function openCookieSettings(reload){
    userClick = true;
    getCookieSettings();
}

function showBasicModal(){
    document.getElementById('cookie-banner').style.display = 'flex';
}

function acceptAll(){
    
    try{
        document.getElementById('cookie-banner').style.display = 'none';
        document.getElementById('cookie-settings').style.display = 'none';
    }
    catch(e){}
    
    if(allCookies === true) return;

    $case = 1;
    allCookies = true; preferencesCookies = true; statisticsCookies = true; marketingCookies = true; 

    const cookieValue = {all: true,necessary: true, preferences: true, statistics: true, marketing: true};  
    const cookieString = JSON.stringify(cookieValue);
    const date = new Date();
    date.setFullYear(date.getFullYear() + 1);
    document.cookie = `patlis-cookie=${cookieString}; path=/; expires=${date.toUTCString()};`;
    //document.cookie = `google-ads-enabled=1; path=/; expires=${date.toUTCString()};`;
    //document.cookie = `google-analytics-enabled=1; path=/; expires=${date.toUTCString()};`;
    sessionStorage.setItem('allow-popup', '1');

    needsReload();
}

function saveSettings(){
    document.getElementById('cookie-settings').style.display = 'none';

    const  newPreferences = document.getElementById('preferences-cookies').checked;
    const  newStatistics = document.getElementById('statistics-cookies').checked;
    const  newMarketing = document.getElementById('marketing-cookies').checked;
    
    if (newPreferences === preferencesCookies && newStatistics === statisticsCookies && newMarketing === marketingCookies) {
        return;
    }
    
    const cookieValue = {necessary: true, preferences: newPreferences, statistics: newStatistics, marketing: newMarketing,
        all: newPreferences && newStatistics && newMarketing
    };
    $case = cookieValue.all ? 1 : 3;
    allCookies = cookieValue.all;
    
    const cookieString = JSON.stringify(cookieValue);
    const date = new Date();
    date.setFullYear(date.getFullYear() + 1);
    document.cookie = `patlis-cookie=${cookieString}; path=/; expires=${date.toUTCString()};`;
    //document.cookie = `google-ads-enabled=${cookieValue.marketing ? 1 : 0}; path=/; expires=${date.toUTCString()};`;
    //document.cookie = `google-analytics-enabled=${cookieValue.statistics ? 1 : 0}; path=/; expires=${date.toUTCString()};`;
    sessionStorage.setItem('allow-popup', '1');
    
    preferencesCookies = newPreferences;
    statisticsCookies = newStatistics;
    marketingCookies = newMarketing;

    needsReload();
}

function needsReload(){
    updateConsentDataLayer(); // Always call this first
    window.location.reload();

    /*
    const hasOpenCookieSettings = document.querySelector('[onclick="openCookieSettings(true)"]') !== null;
    if (hasOpenCookieSettings || reloadPage === true) {
        window.location.reload();
        return;
    } 
        */
}

function showMore(){
    userClick = true;
    getCookieSettings();
}

function getCookieSettings(){   
    document.getElementById('cookie-banner').style.display = 'none';
    try{
        document.getElementById('cookie-settings').remove();
    }
    catch(e){}
    
    const url = '/cookies/cookie-settings';//SOS
    fetch(url)
        .then(response => response.json())
        .then(data => {
            const html = data.html;

            if(userClick === false) {
                location.reload();
                return;
            }

            document.body.insertAdjacentHTML('beforeend', html);
            
            document.getElementById('preferences-cookies').checked = preferencesCookies;
            document.getElementById('statistics-cookies').checked = statisticsCookies;
            document.getElementById('marketing-cookies').checked = marketingCookies;
            document.getElementById('cookie-settings').style.display = 'flex';//open modal
        })
        .catch(error => {
            showMessage(error);
        });
}

function showMessage(error){ console.log(error); }

function toggleElm(elm){
    elm.classList.toggle('ex-d-none');

    let prefix_id = elm.id;
    prefix_id = "prefix_"+prefix_id.replace('collapse_',''); 
    if(elm.classList.contains('ex-d-none')){ 
         document.getElementById(prefix_id).textContent  = "+";
    }else{
        document.getElementById(prefix_id).textContent  = "-";
    }
}
