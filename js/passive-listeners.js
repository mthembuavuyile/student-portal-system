/**
 * Passive Event Listeners Helper
 * Resolves Chrome DevTools [Violation] warnings for scroll-blocking touch/wheel events.
 * 
 * Automatically sets { passive: true } on 'touchstart', 'touchmove', 'wheel', and 'mousewheel'
 * event listeners added by third-party libraries (e.g., Materialize CSS Waves effect).
 */
(function() {
    'use strict';
    var supportsPassive = false;
    try {
        var opts = Object.defineProperty({}, 'passive', {
            get: function() { supportsPassive = true; return true; }
        });
        window.addEventListener('testPassive', null, opts);
        window.removeEventListener('testPassive', null, opts);
    } catch (e) {
        supportsPassive = false;
    }

    if (supportsPassive) {
        var originalAddEventListener = EventTarget.prototype.addEventListener;
        EventTarget.prototype.addEventListener = function(type, listener, options) {
            var passiveEvents = ['touchstart', 'touchmove', 'wheel', 'mousewheel'];
            if (passiveEvents.indexOf(type) !== -1) {
                if (typeof options === 'boolean') {
                    options = { capture: options, passive: true };
                } else if (typeof options === 'object' && options !== null) {
                    if (options.passive === undefined) {
                        options.passive = true;
                    }
                } else if (options === undefined) {
                    options = { passive: true };
                }
            }
            return originalAddEventListener.call(this, type, listener, options);
        };
    }
})();
