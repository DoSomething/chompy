/**
 * Wait until the DOM is ready.
 *
 * @param {Function} fn
 */

export function ready(fn) {
    if (document.readyState !== 'loading') {
        fn();
    } else {
        document.addEventListener('DOMContentLoaded', fn);
    }
}
