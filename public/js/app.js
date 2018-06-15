// A simple function to copy a string to clipboard.
// This works in most modern browsers, although you won't be able to tell if the copy command succeeded.
// See https://github.com/lgarron/clipboard-polyfill for a more robust solution.
function copyToClipboard(str) {
    function listener(e) { e.clipboardData.setData("text/plain", str);
        e.preventDefault(); }
    document.addEventListener("copy", listener);
    document.execCommand("copy");
    document.removeEventListener("copy", listener);
};

