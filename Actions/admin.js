(function(window, document) {

    window.addEventListener("load", function() {
        var loadButton = document.getElementById("buildClients"),
        buildIframe = document.getElementById("buildIframe"),
        buildInProgess = document.getElementById("buildingInProgress"),
        log = document.getElementById("log"),
        timeElement = document.getElementById("time"),
        buildTime = 0,
        lastBuildTime = null;
        interval = null;
        if (window.localStorage) {
            lastBuildTime = window.localStorage.getItem("lastBuildTime");
        }
        if (lastBuildTime !== null) {
            document.getElementById("lastBuildTimeWrapper").setAttribute("style", "");
            document.getElementById("lastBuildTime").innerHTML = lastBuildTime;
        }
        buildIframe.addEventListener("load", function() {
            buildInProgess.setAttribute("style", "display : none;");
            try {
                log.setAttribute("style", "");
                log.innerHTML = buildIframe.contentWindow.document.getElementById("log").innerHTML;
            } catch(event) {}
            if (window.localStorage) {
                window.localStorage.setItem("lastBuildTime", buildTime);
            }
            buildTime = 0;
            timeElement.innerHTML = 0;
            if (interval) {
                window.clearInterval(interval);
            }
            interval = null;
        });
        loadButton.addEventListener("click", function() {
            if (interval) {
                if (!window.confirm("There is a build in progress. Do you want to relaunch ?")) {
                    return;
                }
                time = 0;
                log.setAttribute("style", "display : none;");
                window.clearInterval(interval);
            }
            buildIframe.src = loadButton.getAttribute("data-href");
            buildInProgess.setAttribute("style", "");
            interval = null;
            interval = window.setInterval(function() {
                buildTime += 1;
                timeElement.innerHTML = buildTime;
            }, 1000);
        });
        window.addEventListener("beforeunload", function() {
            if (interval) {
                return "There is a build in progress. Do you want to close the window ?";
            }
        });
    });

})(window, document);