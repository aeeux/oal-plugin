jQuery(document).ready(function ($) {
  fetchCars();

  function fetchCars() {
    $.getJSON("/beta2/wp-json/oal/v1/cars", function (data) {
      var user_data = data[Object.keys(data)[0]];
      var cars = user_data.cars;
      var tbody = $("#oal-cars-table tbody");
      tbody.empty();

      if (cars.length === 0) {
        tbody.append('<tr><td colspan="6">No cars added yet.</td></tr>');
      } else {
        cars.forEach(function (car_group) {
          car_group.vehicles.forEach(function (car) {
            var row = $("<tr>");
            row.append("<td>" + car.id + "</td>");
            row.append("<td>" + car.brand + "</td>");
            row.append("<td>" + car.model + "</td>");
            row.append("<td>" + car.description + "</td>");
            row.append("<td>" + car.price + "</td>");
            row.append("<td>" + car.ydelse + "</td>");
            row.append("<td>" + car.restværdi + "</td>");
            row.append(
              "<td><img src='" +
                car.image +
                "' alt='" +
                car.model +
                "' style='width: 50px; height: auto;'></td>"
            ); // Displaying a small preview of the car image
            row.append(
              '<td><button data-id="' +
                car.id +
                '" class="button button-small oal-delete-car">Delete</button></td>'
            );
            tbody.append(row);
          });
        });
      }
    });
  }

  $("#oal-cars-table").on("click", ".oal-delete-car", function () {
    var car_id = $(this).data("id");
    $.ajax({
      url: oal_ajax.url, // Using the localized script object
      type: "POST",
      data: {
        action: "oal_delete_car",
        id: car_id,
        nonce: oal_ajax.nonce,
      },
      success: function () {
        fetchCars();
      },
      error: function (response) {
        console.error("Error deleting car:", response);
      },
    });
  });

  $("#oal-purge-data").on("click", function () {
    if (
      confirm(
        "Are you sure you want to purge all data? This action is irreversible."
      )
    ) {
      $.ajax({
        url: oal_ajax.url,
        type: "POST",
        data: {
          action: "oal_purge_data",
          nonce: oal_ajax.nonce,
        },
        success: function () {
          fetchCars(); // Refresh the table to show no cars
        },
        error: function (response) {
          console.error("Error purging data:", response);
        },
      });
    }
  });
});
