(function () {

    if (window.patlisAiImageLabels && window.patlisAiImageLabels.enabled === 'no') {
        return;
    }

    if (window.location.href.toLowerCase().indexOf('/kiosk') !== -1) {
        return;
    }

    function getLabelText(status) {
        if (status === 'assisted') {
            return window.patlisAiImageLabels.assistedLabel || '';
        }

        if (status === 'modified') {
            return window.patlisAiImageLabels.modifiedLabel || '';
        }

        if (status === 'generated') {
            return window.patlisAiImageLabels.generatedLabel || '';
        }

        return '';
    }

    function shouldSkipLabel(imageUrl) {
        return (imageUrl || '').toLowerCase().indexOf('/kiosk') !== -1;
    }

    function getRenderedWidth(image) {
        return image.clientWidth || image.getBoundingClientRect().width || image.naturalWidth || 0;
    }

    function getMinimumWidth() {
        var minimumWidth = parseInt(window.patlisAiImageLabels.minWidth, 10);

        return minimumWidth >= 100 && minimumWidth <= 300 ? minimumWidth : 150;
    }

    function updateLabelSize(image, label) {
        var width = getRenderedWidth(image);
        var size = width <= 250 ? 'small-label' : (width <= 576 ? 'medium-label' : 'large-label');

        label.classList.remove('small-label', 'medium-label', 'large-label');
        label.classList.add(size);
    }

    function observeLabelSize(image) {
        if (image.dataset.patlisAiLabelSizeObserved === '1') {
            return;
        }

        image.dataset.patlisAiLabelSizeObserved = '1';
        image.addEventListener('load', function () {
            addLabel(image);
        });

        if (window.ResizeObserver) {
            new ResizeObserver(function () {
                addLabel(image);
            }).observe(image);
        }
    }

    function wrapImage(image) {
        var wrapper = document.createElement('span');
        wrapper.className = 'ai-label-container';
        wrapper.style.position = 'relative';
        wrapper.style.display = 'inline-block';
        wrapper.style.lineHeight = '0';

        if (!image.parentNode) {
            return null;
        }

        image.parentNode.insertBefore(wrapper, image);
        wrapper.appendChild(image);

        return wrapper;
    }

    function getContainer(image) {
        var parent = image.parentElement;

        if (!parent) {
            return null;
        }

        if (parent.classList && parent.classList.contains('ai-label-container')) {
            return parent;
        }

        if (parent.tagName === 'PICTURE' && parent.parentElement && parent.parentElement.tagName === 'A') {
            return parent.parentElement;
        }

        if (parent.tagName === 'A' || parent.tagName === 'PICTURE' || parent.tagName === 'FIGURE') {
            return parent;
        }

        return wrapImage(image);
    }

    function prepareContainer(container) {
        var style = window.getComputedStyle(container);

        container.classList.add('ai-label-container');

        if (style.position === 'static') {
            container.style.position = 'relative';
        }

        if (style.display === 'inline') {
            container.style.display = 'inline-block';
        }
    }

    function addLabel(image) {
        if (image.classList.contains('pswp__img')) {
            return;
        }

        if (shouldSkipLabel(image.currentSrc || image.src)) {
            return;
        }

        var status = image.dataset.patlisAiStatus || '';
        if (status !== 'assisted' && status !== 'generated' && status !== 'modified') {
            return;
        }

        observeLabelSize(image);

        if (getRenderedWidth(image) <= getMinimumWidth()) {
            if (image.patlisAiLabelElement) {
                image.patlisAiLabelElement.remove();
                image.patlisAiLabelElement = null;
                image.dataset.patlisAiLabelApplied = '';
            }
            return;
        }

        if (image.dataset.patlisAiLabelApplied === '1') {
            updateLabelSize(image, image.patlisAiLabelElement);
            return;
        }

        var labelText = getLabelText(status);
        if (!labelText) {
            return;
        }

        var container = getContainer(image);
        if (!container) {
            return;
        }

        prepareContainer(container);

        var label = document.createElement('span');
        label.className = 'ai-label';
        label.setAttribute('aria-label', getLabelText(status));
        label.title = label.getAttribute('aria-label');
        label.textContent = labelText;
        container.appendChild(label);
        image.patlisAiLabelElement = label;
        image.dataset.patlisAiLabelApplied = '1';

        updateLabelSize(image, label);
    }

    function applyLightboxLabels(root) {
        if (!root || !root.querySelectorAll) {
            return;
        }

        var images = [];
        if (root.matches && root.matches('.pswp__img')) {
            images.push(root);
        }

        root.querySelectorAll('.pswp__img').forEach(function (image) {
            images.push(image);
        });

        images.forEach(function (image) {
            var source = image.currentSrc || image.src || '';

            if (shouldSkipLabel(source)) {
                return;
            }

            var trigger = Array.from(document.querySelectorAll('[data-patlis-ai-status][data-pswp-src], [data-patlis-ai-status][href]')).find(function (link) {
                return (link.dataset.pswpSrc || link.href || '') === source;
            });
            var status = trigger ? (trigger.dataset.patlisAiStatus || '') : '';
            var zoomWrap = image.closest('.pswp__zoom-wrap');

            var labelText = getLabelText(status);
            if (!zoomWrap || !labelText || zoomWrap.querySelector('.ai-label')) {
                return;
            }

            image.dataset.patlisAiStatus = status;
            prepareContainer(zoomWrap);

            var label = document.createElement('span');
            label.className = 'ai-label';
            label.setAttribute('aria-label', labelText);
            label.title = label.getAttribute('aria-label');
            label.textContent = labelText;
            zoomWrap.appendChild(label);

            updateLabelSize(image, label);
        });
    }

    function applyLabels(root) {
        if (root.nodeType === Node.ELEMENT_NODE && root.matches('img[data-patlis-ai-status]:not(.pswp__img)')) {
            addLabel(root);
        }

        if (root.querySelectorAll) {
            root.querySelectorAll('img[data-patlis-ai-status]:not(.pswp__img)').forEach(addLabel);
        }
    }

    function initializeLabels() {
        applyLabels(document);

        new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    applyLabels(node);
                    applyLightboxLabels(node);
                });
            });
        }).observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeLabels);
    } else {
        initializeLabels();
    }
}());
