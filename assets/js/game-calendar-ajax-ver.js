jQuery(function ($) {
  $(document).on('click', '[data-url]', function() {
    let url = $(this).attr('data-url');
    if (url) {
      window.location.href = url;
    }
  });

  $(document).on('mouseenter', '[data-url]', function() {
    $(this).css('cursor', 'pointer');
  });

  $(document).on('mouseleave', '[data-url]', function() {
    $(this).css('cursor', 'default');
  });

  $.ajax({
    url: PHP_DATA["ajax_url"],
    method: "POST",
    data: {
      action: "cpbl_game_schedule",
      year: PHP_DATA["year"],
      kindCode: PHP_DATA["game_type"],
    },
    success: (res) => {
      put_game_schedule(res);
    },
  });

  function put_game_schedule(responseData) {
    responseData.forEach((element) => {
      let targetTableTd = $(
        `#${PHP_DATA["id"]} .calendar-table-view td[data-date=${element.GameDate}]>.calendar-day-info`
      );
      let postponedClass =
        element.GameResult != 0 && element.GameResult != 9 ? "game-postponed" : "";
      let gameStatueText =
        element.GameResult != 0 && element.GameResult != 9
          ? element.GameResultName
          : element.GameDateTimeS;

      if (
        $(`#${PHP_DATA["id"]} .calendar-table-view td[data-date=${element.GameDate}]>.calendar-day-info>.game-info-cell`).length > 0
      ) {
        targetTableTd.append(`
          <section class="game-info-cell" hidden data-url="${element.post_url}">
              <div class="game-detail-info">
                  <div>
                      <img src="${element.VisitingTeamImg}" alt="">
                  </div>
                  <div class="game-number">${element.GameSno}</div>
                  <div>
                      <img src="${element.HomeTeamImg}" alt="">
                  </div>
                  <div class="team-score">${element.VisitingScore}</div>
                  <div class="team-versus">${element.GameResultText}</div>
                  <div class="team-score">${element.HomeScore}</div>
              </div>
              <div class="game-location-time">
                  <div>${element.FieldAbbe}</div>
                  <div><span class="${postponedClass}">${gameStatueText}</span></div>
              </div>
          </section>
        `);
        if (
          $(`#${PHP_DATA["id"]} .calendar-table-view td[data-date=${element.GameDate}]>.calendar-day-info>.day-game-switcher`).length == 0
        ) {
          targetTableTd.append(`
            <div class="day-game-switcher" data-gameview="0">
                <button data-gameswitch="prev" class="game-prev" style="visibility:hidden;"></button>
                <button data-gameswitch="next" class="game-next"></button>
            </div>
          `);
        }
      } else {
        targetTableTd.append(`
          <section class="game-info-cell" data-url="${element.post_url}">
              <div class="game-detail-info">
                  <div>
                      <img src="${element.VisitingTeamImg}" alt="">
                  </div>
                  <div class="game-number">${element.GameSno}</div>
                  <div>
                      <img src="${element.HomeTeamImg}" alt="">
                  </div>
                  <div class="team-score">${element.VisitingScore}</div>
                  <div class="team-versus">${element.GameResultText}</div>
                  <div class="team-score">${element.HomeScore}</div>
              </div>
              <div class="game-location-time">
                  <div>${element.FieldAbbe}</div>
                  <div><span class="${postponedClass}">${gameStatueText}</span></div>
              </div>
          </section>
        `);
      }

      let listHomeScore = "";
      let listVisitingScore = "";
      if (element.GameResult == 0 || element.GameResult == 2) {
        listHomeScore = `&nbsp;${element.HomeScore}&nbsp;`;
        listVisitingScore = `&nbsp;${element.VisitingScore}&nbsp;`;
      }

      let targetList = $(
        `#${PHP_DATA["id"]} .calendar-list-view[data-month-list=${element.GameMonth}]`
      );

      if (
        $(`#${PHP_DATA["id"]} .calendar-list-view[data-month-list=${element.GameMonth}]>.calendar-list-ngame`).length > 0
      ) {
        $(`#${PHP_DATA["id"]} .calendar-list-view[data-month-list=${element.GameMonth}]>.calendar-list-ngame`).remove();
      }
      if (
        $(`#${PHP_DATA["id"]} .calendar-list-view[data-month-list=${element.GameMonth}]>section[data-date=${element.GameDate}]`).length > 0
      ) {
        $(
          `#${PHP_DATA["id"]} .calendar-list-view[data-month-list=${element.GameMonth}]>section[data-date=${element.GameDate}] .calendar-list-content`
        ).append(`
          <div class="per-game" data-url="${element.post_url}">
            <div class="per-game-time">
                <span class="${postponedClass}">${gameStatueText}</span>
            </div>
            <div class="per-game-dot"></div>
            <div class="per-game-versus">
                ${element.VisitingTeamName}
                ${listHomeScore}
                &nbsp;${element.GameResultText}&nbsp;
                ${listVisitingScore}
                ${element.HomeTeamName}
            </div>
            <div class="per-game-location">-
                ${element.FieldAbbe}
            </div>
          </div>
        `);
      } else {
        targetList.append(`
          <section class="claendar-list-wrapper" data-date=${element.GameDate}>
              <div class="calendar-list-date">
                  <div>${element.GameDate}</div>
                  <div>${element.GameWeek}</div>
              </div>
              <div class="calendar-list-content">
                  <div class="per-game" data-url="${element.post_url}">
                      <div class="per-game-time">
                          <span class="${postponedClass}">${gameStatueText}</span>
                      </div>
                      <div class="per-game-dot"></div>
                      <div class="per-game-versus">
                          ${element.VisitingTeamName}
                          ${listVisitingScore}
                          &nbsp;${element.GameResultText}&nbsp;
                          ${listHomeScore}
                          ${element.HomeTeamName}
                      </div>
                      <div class="per-game-location">-
                          ${element.FieldAbbe}
                      </div>
                  </div>
              </div>
          </section>
        `);
      }
    });
    $(".calendar-loading-mask").fadeOut(400, () => { });

    let animateFinished = true;
    $(".month-switcher-btn").on("click", function () {
      if (!animateFinished) return;

      let triggerAction = $(this).attr("data-switch");
      let monthTextArray = JSON.parse(
        $(this).parents(".year-month-title").attr("data-all-month")
      );

      let currentMonthKey = parseInt(
        $(this).parents(".year-month-title").attr("data-display-month")
      );

      let currentMonth = $(this)
        .parents(".game-calendar-wrapper")
        .children(`.game-calendar-content[data-month=${currentMonthKey}]`);

      let allMonth = $(this)
        .parents(".game-calendar-wrapper")
        .children(".game-calendar-content");

      animateFinished = false;
      if (triggerAction === "prev") {
        if (currentMonthKey - 1 >= 0) {
          $(this)
            .parents(".year-month-title")
            .attr("data-display-month", currentMonthKey - 1);
          $(this)
            .parents(".year-month-title")
            .children(".year-month-text")
            .text(monthTextArray[currentMonthKey - 1]);
          $(currentMonth).fadeOut(200, function () {
            $(allMonth[currentMonthKey - 1]).fadeIn(200, function () {
              animateFinished = true;
            });
          });
        } else {
          animateFinished = true;
        }
      } else if (triggerAction === "next") {
        if (currentMonthKey < allMonth.length - 1) {
          $(this)
            .parents(".year-month-title")
            .attr("data-display-month", currentMonthKey + 1);
          $(this)
            .parents(".year-month-title")
            .children(".year-month-text")
            .text(monthTextArray[currentMonthKey + 1]);
          $(currentMonth).fadeOut(200, function () {
            $(allMonth[currentMonthKey + 1]).fadeIn(200, function () {
              animateFinished = true;
            });
          });
        } else {
          animateFinished = true;
        }
      }
    });

    $(".day-game-switcher>button").on("click", function (e) {
      e.preventDefault();
      let triggerAction = $(this).attr("data-gameswitch");
      let allGames = $(this)
        .parents(".calendar-day-info")
        .children("section");
      let currentView = parseInt(
        $(this).parent(".day-game-switcher").attr("data-gameview")
      );

      if (triggerAction == "prev") {
        if (currentView > 0) {
          $(allGames[currentView]).attr("hidden", true);
          $(allGames[currentView - 1]).removeAttr("hidden");
          $(this)
            .parent(".day-game-switcher")
            .attr("data-gameview", currentView - 1);
          $(this).next("button").css("visibility", "visible");
          if ($(this).parent(".day-game-switcher").attr("data-gameview") == 0) {
            $(this).css("visibility", "hidden");
          }
        }
      } else if (triggerAction == "next") {
        if (currentView < allGames.length - 1) {
          $(allGames[currentView]).attr("hidden", true);
          $(allGames[currentView + 1]).removeAttr("hidden");
          $(this)
            .parent(".day-game-switcher")
            .attr("data-gameview", currentView + 1);
          $(this).prev("button").css("visibility", "visible");
          if (
            $(this).parent(".day-game-switcher").attr("data-gameview") ==
            allGames.length - 1
          ) {
            $(this).css("visibility", "hidden");
          }
        }
      }
    });

    $(".display-tabs>button").on("click", function (e) {
      // e.preventDefault();
      let allCalendars = $(this)
        .parents(".game-calendar-wrapper")
        .children(".game-calendar-content");
      let triggerView = $(this).attr("data-calendarview");

      $(this)
        .siblings("button")
        .each(function () {
          $(this).removeClass("calendar-tab-active");
        });

      $(this).addClass("calendar-tab-active");
      $(allCalendars).each(function () {
        if (triggerView === "list") {
          $(this).children(".calendar-table-view").css("display", "none");
          $(this).children(".calendar-list-view").css("display", "block");
        } else if (triggerView === "month") {
          $(this).children(".calendar-list-view").css("display", "none");
          $(this).children(".calendar-table-view").css("display", "block");
        }
      });
    });

    $(window).on("resize", function () {
      let mq = window.matchMedia("(max-width: 1023px)").matches;
      let allCalendars = $(".game-calendar-wrapper").children(
        ".game-calendar-content"
      );
      let allSwitchButtons = $(".display-tabs>button");
      if (mq) {
        allSwitchButtons.each(function () {
          $(this).removeClass("calendar-tab-active");
          if ($(this).attr("data-calendarview") == "list")
            $(this).addClass("calendar-tab-active");
        });
        $(allCalendars).each(function () {
          $(this).children(".calendar-table-view").css("display", "none");
          $(this).children(".calendar-list-view").css("display", "block");
        });
      }
    });
  }
});