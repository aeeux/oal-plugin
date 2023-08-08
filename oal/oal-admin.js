jQuery(document).ready(function ($) {
  // Fetch cars
  function fetchCars() {
    $.getJSON("/beta2/wp-json/oal/v1/cars", function (allCars) {
      var tbody = $("#oal-cars-table tbody");
      tbody.empty();

      // Check if cars are empty
      if (Object.keys(allCars).length === 0) {
        tbody.append('<tr><td colspan="7">No cars found.</td></tr>');
        return;
      }

      // Loop through users
      $.each(allCars, function (username, brands) {
        // Loop through brands
        $.each(brands, function (brand, models) {
          // Loop through models
          $.each(models, function (model, car) {
            var row = $("<tr></tr>");
            row.append("<td>" + car.id + "</td>");
            row.append("<td>" + username + "</td>");
            row.append("<td>" + brand + "</td>");
            row.append("<td>" + model + "</td>");
            row.append("<td>" + car.description + "</td>");
            row.append("<td>$" + car.price.toFixed(2) + "</td>");
            row.append(
              '<td><img src="' + car.image + '" width="50" height="50" /></td>'
            );

            var actions = $("<td></td>");
            actions.append(
              '<button class="button delete-car" data-id="' +
                car.id +
                '">Delete</button>'
            );
            row.append(actions);

            tbody.append(row);
          });
        });
      });
    });
  }

  fetchCars();

  // Delete car
  $(document).on("click", ".delete-car", function () {
    var id = $(this).data("id");
    var confirmDelete = confirm(
      "Are you sure you want to delete the car with ID: " + id + "?"
    );

    if (!confirmDelete) {
      return;
    }

    $.ajax({
      method: "POST",
      url: oal_ajax.url,
      data: {
        action: "oal_delete_car",
        nonce: oal_ajax.nonce,
        id: id,
      },
      success: function (response) {
        if (response.success) {
          alert("Car deleted successfully.");
          fetchCars();
        } else {
          alert("Error deleting the car.");
        }
      },
    });
  });

  // Purge all data
  $("#oal-purge-data").on("click", function () {
    var confirmPurge = confirm("Are you sure you want to purge all car data?");

    if (!confirmPurge) {
      return;
    }

    $.ajax({
      method: "POST",
      url: oal_ajax.url,
      data: {
        action: "oal_purge_data",
        nonce: oal_ajax.nonce,
      },
      success: function (response) {
        if (response.success) {
          alert("All car data purged successfully.");
          fetchCars();
        } else {
          alert("Error purging car data.");
        }
      },
    });
  });
});
