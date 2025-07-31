jQuery(function ($) {
  $(".batter-stats-btn > div").on("click", function () {
    let tabTarget = $(this).attr("data-tab");
    $(this)
      .parents(".players-stats-thead")
      .children(".players-stats-th")
      .each(function () {
        $(this).children("div").removeClass("stats-tab-active");
      });
    $(this).addClass("stats-tab-active");
    $(this)
      .parents(".players-stats-table")
      .children(".players-stats-tbody")
      .children(".players-stats-display")
      .each(function () {
        $(this).addClass("players-tab-hidden");
        if ($(this).attr("data-tabcontent") == tabTarget) {
          $(this).removeClass("players-tab-hidden");
        }
      });
  });
});
