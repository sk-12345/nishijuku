const params = new URLSearchParams(location.search);
const err = params.has("err");

const errMsg = document.getElementById("errMsg");
if (errMsg) {
    errMsg.style.display = err ? "block" : "none";
}
