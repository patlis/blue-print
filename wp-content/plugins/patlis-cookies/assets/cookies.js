let allCookies = false;
let preferencesCookies = false;
let statisticsCookies = false;
let marketingCookies = false;
// check if cookie "patlis-cookie" exist
const cookie = document.cookie.split('; ').find(row => row.startsWith('patlis-cookie='));
let $loadBasicModal = false;
// get domain
const ourDomain = window.location.hostname.split(".").slice(-2).join("."); //ΣΟΣ αλλαγη
//set case
let $case = 0;
// check if cookie "cookie-resources" exist
let $settingsExist = sessionStorage.getItem('cookie-resources') ? true : false;
let $sourcesData = JSON.parse(sessionStorage.getItem('cookie-resources') || '[]');

let $allowedCategories = [1,5]; // necessary cookies (1) and not categorized (5)
let userClick = false;
let reloadPage = false;

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

// 2. page load. set $sourcesData & allowedCategories
if(statisticsCookies === true) $allowedCategories.push(2);
if(marketingCookies === true) $allowedCategories.push(3);
if(preferencesCookies === true) $allowedCategories.push(4);

// 3 page load. check all iframes & scripts that allready exist
checkAll();
function checkAll(){
    document.querySelectorAll('iframe, script').forEach(function(node) { 
        const tagName = node.tagName ? node.tagName.toLowerCase() : '';
        if (tagName === 'iframe') {oneIframe(node);}
        if (tagName === 'script') {oneScript(node);}
    });
}

// 4 page load. set mutation observer
mutation();
function mutation(){
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    const tagName = node.tagName ? node.tagName.toLowerCase() : '';

                    if (tagName === 'script') { oneScript(node); }
                    if (tagName === 'iframe' && (node.src || node.getAttribute('data-src') || node.getAttribute('blocked-src')|| node.getAttribute('blocked-data-src')))
                        { 
                            oneIframe(node); 
                        }   
                } 
           });
       });
    });
    
    observer.observe(document.documentElement, { childList: true, subtree: true });
}

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
    if(reload === true) reloadPage = true;
    userClick = true;
    getCookieSettings();
}

function oneScript(node){
    const src = node.getAttribute('src');
    const dataSrc = node.getAttribute('data-src');
    const checkSrc= src || dataSrc;

    if(!checkSrc) return; //  inline script
    
    const $OurDomain = isFromOurDomain(checkSrc);
    const $isinBlackList = isBlockedUrl(checkSrc);

    if($case === 1 && node.type === 'text/plain'){
        const newScript = document.createElement('script');
        newScript.type = 'text/javascript';
        newScript.src = src;
        node.parentNode.insertBefore(newScript, node);
        node.remove();
        return;
    }

    if($case === 2 ){
        node.type = $OurDomain ? 'text/javascript' : 'text/plain';
        return; 
    }

    if($case === 3 || $case === 5){
        node.type = ($isinBlackList && !$OurDomain) ? 'text/plain' : 'text/javascript';
    }
}javascript:;

function getFullUrl(url) {
    if (!url) return '';
    if (url.startsWith('//')) { return `${location.protocol}${url}`;}
    if (!url.startsWith('http')) { return `${location.origin}/${url}`;}
    return url;
}

function isFromOurDomain(url) {
    if (!url) return true; // return false if url is null or undefined

    if (!url.startsWith('http') && !url.startsWith('//')) {
        url = getFullUrl(url);
    }

    // we create a URL object to extract the hostname
    const urlObj = new URL(url, window.location.origin);
    const hostname = urlObj.hostname;

    // return true only if hostname ends with ourDomain
    return hostname.endsWith(ourDomain);
}

function isBlockedUrl(url) {
    if (!url) return false; // return false if url is null or undefined

    for (const source of $sourcesData) {
        if (url.includes(source.url)) {           
            if (!$allowedCategories.includes(source.category)) {return true;} 
            else {return false;}
        } 
    }

    return true;
}

function oneIframe(node){
    const src = node.getAttribute('src');
    const dataSrc = node.getAttribute('data-src');
    const blockedSrc = node.getAttribute('blocked-src');
    const blockedDataSrc = node.getAttribute('blocked-data-src');
    const checkSrc= src || dataSrc || blockedSrc || blockedDataSrc;

    //----all cookies accepted
    if ($case === 1) {
        if (blockedSrc) {
            node.setAttribute('src', blockedSrc);
            node.removeAttribute('blocked-src');
            node.classList.remove('ex-blocked-iframe', 'ex-video');
            refreshNodeAndRemovePlaceholder(node);
        }
        
        if (blockedDataSrc) {
            node.setAttribute('data-src', blockedDataSrc);
            node.removeAttribute('blocked-data-src');
            node.classList.remove('ex-blocked-iframe', 'ex-video');
            node.classList.add('lazy');
            refreshNodeAndRemovePlaceholder(node);
        }
    }

    //----Cookie not found
    if($case === 2 ){
        const $OurDomain = isFromOurDomain(checkSrc);
        if (!$OurDomain) { placeHolder(node); }
        else{
            if(blockedSrc){
                node.src = blockedSrc;
                node.removeAttribute('blocked-src');
            }
            if(blockedDataSrc){
                node.setAttribute('data-src', blockedDataSrc);
                node.removeAttribute('blocked-data-src');
                node.classList.add('lazy');
            }
        }
    }

    //----accepted some or only necessary cookies
    if ($case === 3 || $case === 5) {      
        const $OurDomain = isFromOurDomain(checkSrc);
        $isinBlackList = isBlockedUrl(checkSrc);
    
        if ($isinBlackList && !$OurDomain) { 
            placeHolder(node); 
        }
        else{
            if (blockedSrc) {
                node.setAttribute('src', blockedSrc);
                node.removeAttribute('blocked-src');
                node.classList.remove('ex-blocked-iframe', 'ex-video');
                refreshNodeAndRemovePlaceholder(node);
            }
            
            if (blockedDataSrc) {
                node.setAttribute('data-src', blockedDataSrc);
                node.removeAttribute('blocked-data-src');
                node.classList.remove('ex-blocked-iframe', 'ex-video');
                node.classList.add('lazy');
                refreshNodeAndRemovePlaceholder(node);
            }
        }
    }

    if (node.classList.contains('lazy')) {
        applyLazyLoadingToNewElements([node]);
    }
}

function refreshNodeAndRemovePlaceholder(node) {
    const parent = node.parentNode;
    const nextSibling = node.nextSibling;
    parent.removeChild(node);
    parent.insertBefore(node, nextSibling);

    if (nextSibling && nextSibling.classList && nextSibling.classList.contains('ex-placeholder')) {
        nextSibling.remove();
    }
}

function placeHolder(node) {  
    if (node.getAttribute('src')) { node.setAttribute('blocked-src', node.getAttribute('src')); }
    if (node.getAttribute('data-src')) { node.setAttribute('blocked-data-src', node.getAttribute('data-src')); }
    node.src = '';
    node.setAttribute('data-src', '');

    node.classList.add('ex-blocked-iframe');
    const srcAttributes = [node.getAttribute('src'), node.getAttribute('data-src'), node.getAttribute('blocked-src'), node.getAttribute('blocked-data-src')];
    if (srcAttributes.some(url => url && (url.includes('youtu.be') || url.includes('youtube.com') || url.includes('vimeo') || url.includes('youtube-nocookie.com')))) {
        node.classList.add('ex-video');
    }
    // if src exist then blocked-src=src
    if (node.getAttribute('src')) { node.setAttribute('blocked-src', node.getAttribute('src')); }
    if (node.getAttribute('data-src')) {  node.setAttribute('blocked-data-src', node.getAttribute('data-src')); }
    
     // check if placeholder already exist
     if (node.nextSibling && node.nextSibling.classList && node.nextSibling.classList.contains('ex-placeholder')) {
        return; // if placeholder already exist, do nothing
    }

    //--add placeholder after iframe
    const placeholder = document.createElement('div');
    placeholder.classList.add('ex-placeholder');
    placeholder.innerHTML = `
        <p>Zur Anzeige dieser Inhalte müssen Cookies zurückgesetz werden.</p>
        <strong><button onClick="openCookieSettings()" class="ex-btn ex-btn-outline-primary">Cookie-Einstellungen öffnen</strong>`;

        node.insertAdjacentElement('afterend', placeholder);
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

    $allowedCategories = [1,2,3,4,5];
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

    $allowedCategories = [1,5];
    if(statisticsCookies === true) $allowedCategories.push(2);
    if(marketingCookies === true) $allowedCategories.push(3);
    if(preferencesCookies === true) $allowedCategories.push(4);

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
            const sources = data.resources;
            sessionStorage.setItem('cookie-resources', JSON.stringify(sources));
            $settingsExist= true;

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

function applyLazyLoadingToNewElements(newElements) {
    newElements.forEach(function (lazyElement) {
        if ("IntersectionObserver" in window) {
            const observer = new IntersectionObserver(function (entries, observer) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        if (lazyElement.tagName.toLowerCase() === 'img' || lazyElement.tagName.toLowerCase() === 'iframe') {
                            lazyElement.src = lazyElement.dataset.src;
                        } else if (lazyElement.tagName.toLowerCase() === 'div' && lazyElement.dataset.src) {
                            lazyElement.style.backgroundImage = 'url(' + lazyElement.dataset.src + ')';
                        }
                        lazyElement.classList.remove("lazy");
                        observer.unobserve(lazyElement);
                    }
                });
            });

            observer.observe(lazyElement);
        } else {
            // Fallback if not support IntersectionObserver
            document.addEventListener("scroll", lazyLoad);
            window.addEventListener("resize", lazyLoad);
            window.addEventListener("orientationchange", lazyLoad);
        }
    });
}