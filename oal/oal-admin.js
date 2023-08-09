jQuery(document).ready(function ($) {
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
          location.reload(); // Refresh the page to reflect the change
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
          location.reload(); // Refresh the page to reflect the change
        } else {
          alert("Error purging car data.");
        }
      },
    });
  });
});
