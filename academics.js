var events = [];

fetch("events.json")
.then(function(response) {
    return response.json();
})
.then(function(data) {
    events = data;

    var list = "";

    for (var i = 0; i < events.length; i++) {
        list += "<p>" + events[i].name + "</p>";
    }

    document.getElementById("event-list").innerHTML = list;
});

function searchEvent() {

    var search = document.getElementById("search").value.toLowerCase();
    var result = "";

    for (var i = 0; i < events.length; i++) {

        if (events[i].name.toLowerCase() == search) {

            result += "<h3>" + events[i].name + "</h3>";
            result += "<p>Date: " + events[i].date + "</p>";
            result += "<p>Place: " + events[i].place + "</p>";
            result += "<p>" + events[i].description + "</p>";
        }
    }

    document.getElementById("event-list").innerHTML = result;
}