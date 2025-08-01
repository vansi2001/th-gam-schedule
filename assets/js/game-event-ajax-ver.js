jQuery(function ($) {
  $.ajax({
    url: RECENTLY_PHP_DATA["ajax_url"],
    method: "POST",
    data: {
      action: "cpbl_game_recently",
      year: RECENTLY_PHP_DATA["year"],
      kindCode: RECENTLY_PHP_DATA["game_type"],
      totalGames: RECENTLY_PHP_DATA["total"],
      futureGames: RECENTLY_PHP_DATA["future_game"],
    },
    success: (res) => {
      putRecentlyGames(res);
    },
  });

  function putRecentlyGames(responseData) {
    let gameCardHtml = "";
    let cpblGameLink = [];
    responseData.forEach((element, key) => {
      let thGameToday = element.GameToday === "T" ? "th-game-today" : "";
      cpblGameLink[
        key
      ] = `https://www.cpbl.com.tw/box/index?gameSno=${element.GameSno}&year=${RECENTLY_PHP_DATA["year"]}&kindCode=${RECENTLY_PHP_DATA["game_type"]}`;
      gameCardHtml += `
        <div class="th-game-card th-game-bg ${thGameToday}" data-scroll="${key}">
          <div class="th-game-info">
              <span class="game-info-text">${element.GameDate}</span>
              <span class="game-info-text"><i class="fas fa-map-marker-alt"></i>${element.FieldAbbe}</span>
          </div>
          <div class="th-game-result">
              <div>
                  <div class="th-game-logo">
                      <img src="${element.VisitingImg}" alt="">
                  </div>
                  <div class="game-home-away">AWAY</div>
              </div>
              <div class="th-game-score">
                  <div class="game-score-title">${element.GameResultName}</div>
                  <div class="game-score-text">${element.VisitingScore} : ${element.HomeScore}</div>
              </div>
              <div>
                  <div class="th-game-logo">
                      <img src="${element.HomeTeamImg}" alt="">
                  </div>
                  <div class="game-home-away">HOME</div>
              </div>
          </div>
        </div>
        `;
    });
    $(".th-recently-mask").fadeOut(400);
    if (gameCardHtml.length <= 0) return;
    $(`.th-game-wrapper`).html(gameCardHtml);
    $.event.special.touchstart = {
      setup: function (_, ns, handle) {
        if (ns.includes("noPreventDefault")) {
          this.addEventListener("touchstart", handle, { passive: false });
        } else {
          this.addEventListener("touchstart", handle, { passive: true });
        }
      },
    };

    $.event.special.touchmove = {
      setup: function (_, ns, handle) {
        if (ns.includes("noPreventDefault")) {
          this.addEventListener("touchmove", handle, { passive: false });
        } else {
          this.addEventListener("touchmove", handle, { passive: true });
        }
      },
    };

    //Event Listener
    //監聽prev btn、next btn click事件
    $(".th-game-recently").each(function (index) {
      $(this)
        .children(".th-move")
        .on("click", function (e) {
          clickMoving(e, $(this), index);
        });
    });

    //監聽視窗載入，調整當日比賽focus
    $(document).ready(function () {
      $(".th-game-wrapper").each(function (index) {
        let gameKey = "game-" + index;
        if (typeof dragControl[gameKey] === "undefined") {
          dragControl[gameKey] = initDragControl();
        }
        let mq = window.matchMedia("(max-width: 767px)").matches;
        let items = $(this).children(".th-game-card");
        if (!mq) {
          if (
            $(items[dragControl[gameKey].currentFirst + 1]).hasClass(
              "th-game-today"
            )
          ) {
            if ($(this).hasClass("focus-scale")) {
              $(items[dragControl[gameKey].currentFirst]).addClass(
                "th-game-transform-side"
              );
              $(items[dragControl[gameKey].currentFirst + 1]).addClass(
                "th-game-transform"
              );
              $(items[dragControl[gameKey].currentFirst + 2]).addClass(
                "th-game-transform-side"
              );
            }
            if ($(this).hasClass("focus-border")) {
              $(items[dragControl[gameKey].currentFirst + 1]).addClass(
                "th-game-focus-border"
              );
            }
          }
        } else {
          if (
            $(items[dragControl[gameKey].currentFirst]).hasClass(
              "th-game-today"
            )
          ) {
            if ($(this).hasClass("focus-border")) {
              $(items[dragControl[gameKey].currentFirst]).addClass(
                "th-game-focus-border"
              );
            }
          }
        }
      });
    });

    //監聽縮放視窗時，保持scroll位置
    $(window).on("resize", function () {
      $(".th-game-wrapper").each(function (index) {
        let gameKey = "game-" + index;
        if (typeof dragControl[gameKey] === "undefined") {
          dragControl[gameKey] = initDragControl();
        }
        let mq = window.matchMedia("(max-width: 767px)").matches;
        let items = $(this).children(".th-game-card");
        items.each(function () {
          $(this).removeClass("th-game-transform-side");
          $(this).removeClass("th-game-transform");
          $(this).removeClass("th-game-focus-border");
        });
        if (!mq) {
          let items_length = $(this).children(".th-game-card").length;
          if (items_length - 3 < dragControl[gameKey].currentFirst)
            dragControl[gameKey].currentFirst = items_length - 3;
          if (
            $(items[dragControl[gameKey].currentFirst + 1]).hasClass(
              "th-game-today"
            )
          ) {
            if ($(this).hasClass("focus-scale")) {
              $(items[dragControl[gameKey].currentFirst]).addClass(
                "th-game-transform-side"
              );
              $(items[dragControl[gameKey].currentFirst + 1]).addClass(
                "th-game-transform"
              );
              $(items[dragControl[gameKey].currentFirst + 2]).addClass(
                "th-game-transform-side"
              );
            }
            if ($(this).hasClass("focus-border")) {
              $(items[dragControl[gameKey].currentFirst + 1]).addClass(
                "th-game-focus-border"
              );
            }
          }
        } else {
          if (
            $(items[dragControl[gameKey].currentFirst]).hasClass(
              "th-game-today"
            )
          ) {
            if ($(this).hasClass("focus-border")) {
              $(items[dragControl[gameKey].currentFirst]).addClass(
                "th-game-focus-border"
              );
            }
          }
        }
        $(this).scrollLeft(
          $(this).children(".th-game-card")[dragControl[gameKey].currentFirst]
            .offsetLeft
        );
      });
    });

    //監聽拖曳事件
    $(".th-game-wrapper").each(function (index) {
      let gamesLength = $(this).children(".th-game-card").length;
      let gameKey = "game-" + index;

      //click事件
      $(this).on("mousedown", function (e) {
        let mq = window.matchMedia("(max-width: 767px)").matches;
        let blockWidth = $(this).width();
        if (mq && gamesLength > 1) {
          dragStart(e, $(this), "click", gameKey, blockWidth);
        } else if (!mq && gamesLength > 3) {
          dragStart(e, $(this), "click", gameKey, blockWidth);
        }
      });

      $(this).on("mousemove", function (e) {
        dragRunning(e, $(this), "click", gameKey);
      });

      $(this).on("mouseup", function (e) {
        stopDrag(gameKey, $(this));
        if (Math.abs(dragControl[gameKey].mouseDist) < 80) {
          let linkIndex = "";
          if ($(e.target).hasClass("th-game-card")) {
            linkIndex = $(e.target).attr("data-scroll");
          } else {
            linkIndex = $(e.target)
              .parents(".th-game-card")
              .attr("data-scroll");
          }
          if (linkIndex.length > 0)
            window.open(
              cpblGameLink[linkIndex],
              "_blank",
              "noopener, noreferrer"
            );
        }
      });

      $(this).on("mouseleave", function () {
        stopDrag(gameKey, $(this));
      });

      //touch事件
      $(this).on("touchstart", function (e) {
        let mq = window.matchMedia("(max-width: 767px)").matches;
        let blockWidth = $(this).width();
        if (mq && gamesLength > 1) {
          dragStart(e, $(this), "touch", gameKey, blockWidth);
        } else if (!mq && gamesLength > 3) {
          dragStart(e, $(this), "touch", gameKey, blockWidth);
        }
      });

      $(this).on("touchmove", function (e) {
        dragRunning(e, $(this), "touch", gameKey);
      });

      $(this).on("touchend", function () {
        stopDrag(gameKey, $(this));
        if (Math.abs(dragControl[gameKey].mouseDist) < 80) {
          let linkIndex = "";
          if ($(e.target).hasClass("th-game-card")) {
            linkIndex = $(e.target).attr("data-scroll");
          } else {
            linkIndex = $(e.target)
              .parents(".th-game-card")
              .attr("data-scroll");
          }
          if (linkIndex.length > 0)
            window.open(
              cpblGameLink[linkIndex],
              "_blank",
              "noopener, noreferrer"
            );
        }
      });
    });

    //控制項
    let dragControl = {};

    //functions

    //初始化控制項
    function initDragControl() {
      return {
        draggable: false,
        scrollLeft: 0,
        startPageX: 0,
        mouseDist: 0,
        offsetDone: true,
        currentFirst: 0,
        width: 0,
      };
    }

    function dragStart(event, instance, trigger, gameKey, width) {
      if (typeof dragControl[gameKey] === "undefined") {
        dragControl[gameKey] = initDragControl();
      }

      if (trigger !== "touch") {
        event.preventDefault();
      }
      dragControl[gameKey].mouseDist = 0;
      dragControl[gameKey].draggable = true;
      dragControl[gameKey].width = width;
      dragControl[gameKey].startPageX =
        trigger === "touch"
          ? event.originalEvent.touches[0].pageX
          : event.pageX;
      dragControl[gameKey].scrollLeft = instance.scrollLeft();
    }

    function dragRunning(event, instance, trigger, gameKey) {
      if (typeof dragControl[gameKey] === "undefined") return;
      if (!dragControl[gameKey].draggable) return;
      if (trigger !== "touch") {
        event.preventDefault();
      }
      let valuePageX =
        trigger === "touch"
          ? event.originalEvent.touches[0].pageX
          : event.pageX;
      let mouseDist = dragControl[gameKey].startPageX - valuePageX;
      dragControl[gameKey].mouseDist = mouseDist;
      if (!dragControl[gameKey].offsetDone) return;
      instance.scrollLeft(dragControl[gameKey].scrollLeft + mouseDist);
    }

    function stopDrag(gameKey, instance) {
      if (typeof dragControl[gameKey] === "undefined") return;
      if (!dragControl[gameKey].draggable) return;
      if (!dragControl[gameKey].offsetDone) {
        dragControl[gameKey].draggable = false;
        return;
      }
      let items = instance.children(".th-game-card");
      let mq = window.matchMedia("(max-width: 767px)").matches;
      dragControl[gameKey].offsetDone = false;
      let fixValue = 6;
      let lastPosition = dragControl[gameKey].currentFirst;
      if (mq) fixValue = 4;

      if (
        dragControl[gameKey].mouseDist > 0 &&
        dragControl[gameKey].width / fixValue < dragControl[gameKey].mouseDist
      ) {
        //next
        if (mq) {
          if (items.length - 1 > dragControl[gameKey].currentFirst) {
            dragControl[gameKey].currentFirst++;
          }
        } else {
          if (items.length - 3 > dragControl[gameKey].currentFirst) {
            dragControl[gameKey].currentFirst++;
          }
        }
      } else if (
        dragControl[gameKey].mouseDist < 0 &&
        dragControl[gameKey].width / fixValue <
          Math.abs(dragControl[gameKey].mouseDist)
      ) {
        //prev
        if (dragControl[gameKey].currentFirst > 0) {
          dragControl[gameKey].currentFirst--;
        }
      }

      if (lastPosition == 0 && dragControl[gameKey].currentFirst == 0) {
      } else if (
        lastPosition == items.length - 3 &&
        dragControl[gameKey].currentFirst == items.length - 3
      ) {
      } else {
        $(items).each(function () {
          $(this).removeClass(
            "th-game-transform-side th-game-transform th-game-focus-border th-game-transform-side"
          );
        });
      }
      instance.animate(
        { scrollLeft: items[dragControl[gameKey].currentFirst].offsetLeft },
        200,
        function () {
          if (!mq) {
            if (
              $(items[dragControl[gameKey].currentFirst + 1]).hasClass(
                "th-game-today"
              )
            ) {
              if (instance.hasClass("focus-scale")) {
                $(items[dragControl[gameKey].currentFirst]).addClass(
                  "th-game-transform-side"
                );
                $(items[dragControl[gameKey].currentFirst + 1]).addClass(
                  "th-game-transform"
                );
                $(items[dragControl[gameKey].currentFirst + 2]).addClass(
                  "th-game-transform-side"
                );
              }
              if (instance.hasClass("focus-border")) {
                $(items[dragControl[gameKey].currentFirst + 1]).addClass(
                  "th-game-focus-border"
                );
              }
            }
          } else {
            let thisGameToday = $(
              items[dragControl[gameKey].currentFirst]
            ).hasClass("th-game-today");
            if (thisGameToday) {
              if (instance.hasClass("focus-border")) {
                $(items[dragControl[gameKey].currentFirst]).addClass(
                  "th-game-focus-border"
                );
              }
            }
          }
          instance.scrollLeft(
            items[dragControl[gameKey].currentFirst].offsetLeft
          );
          dragControl[gameKey].offsetDone = true;
        }
      );

      dragControl[gameKey].draggable = false;
    }

    function clickMoving(event, instance, index) {
      event.preventDefault();
      let gameKey = "game-" + index;
      if (typeof dragControl[gameKey] === "undefined") {
        dragControl[gameKey] = initDragControl();
      }
      if (!dragControl[gameKey].offsetDone) return;

      const actionBtn = instance.attr("data-move");
      let items;
      dragControl[gameKey].offsetDone = false;
      let mq = window.matchMedia("(max-width: 767px)").matches;
      let lastPosition = dragControl[gameKey].currentFirst;
      if (actionBtn === "prev") {
        items = instance.next(".th-game-wrapper").children(".th-game-card");
        if (mq) {
          if (dragControl[gameKey].currentFirst > 0) {
            dragControl[gameKey].currentFirst -= 1;
          }
        } else {
          if (items.length - 3 > dragControl[gameKey].currentFirst) {
            if (dragControl[gameKey].currentFirst > 0)
              dragControl[gameKey].currentFirst -= 1;
          } else {
            dragControl[gameKey].currentFirst = items.length - 4;
          }
        }

        if (items[dragControl[gameKey].currentFirst]) {
          if (lastPosition == 0 && dragControl[gameKey].currentFirst == 0) {
          } else {
            $(items).each(function () {
              $(this).removeClass(
                "th-game-transform-side th-game-transform th-game-focus-border th-game-transform-side"
              );
            });
          }
          instance.next(".th-game-wrapper").animate(
            {
              scrollLeft: items[dragControl[gameKey].currentFirst].offsetLeft,
            },
            200,
            function () {
              if (!mq) {
                let prevGameToday = $(
                  items[dragControl[gameKey].currentFirst + 1]
                ).hasClass("th-game-today");
                if (prevGameToday) {
                  if (
                    instance.next(".th-game-wrapper").hasClass("focus-scale")
                  ) {
                    $(items[dragControl[gameKey].currentFirst]).addClass(
                      "th-game-transform-side"
                    );
                    $(items[dragControl[gameKey].currentFirst + 1]).addClass(
                      "th-game-transform"
                    );
                    $(items[dragControl[gameKey].currentFirst + 2]).addClass(
                      "th-game-transform-side"
                    );
                  }
                  if (
                    instance.next(".th-game-wrapper").hasClass("focus-border")
                  ) {
                    $(items[dragControl[gameKey].currentFirst + 1]).addClass(
                      "th-game-focus-border"
                    );
                  }
                }
              } else {
                let thisGameToday = $(
                  items[dragControl[gameKey].currentFirst]
                ).hasClass("th-game-today");
                if (thisGameToday) {
                  if (
                    instance.next(".th-game-wrapper").hasClass("focus-border")
                  ) {
                    $(items[dragControl[gameKey].currentFirst]).addClass(
                      "th-game-focus-border"
                    );
                  }
                }
              }
              instance
                .prev(".th-game-wrapper")
                .scrollLeft(
                  items[dragControl[gameKey].currentFirst].offsetLeft
                );
              dragControl[gameKey].offsetDone = true;
            }
          );
        }
      } else if (actionBtn === "next") {
        items = instance.prev(".th-game-wrapper").children(".th-game-card");
        if (mq) {
          if (items.length - 1 > dragControl[gameKey].currentFirst) {
            dragControl[gameKey].currentFirst += 1;
          }
        } else {
          if (items.length - 3 > dragControl[gameKey].currentFirst) {
            dragControl[gameKey].currentFirst += 1;
          } else {
            dragControl[gameKey].currentFirst = items.length - 3;
          }
        }

        if (items[dragControl[gameKey].currentFirst]) {
          if (
            lastPosition == items.length - 3 &&
            dragControl[gameKey].currentFirst == items.length - 3
          ) {
          } else {
            $(items).each(function () {
              $(this).removeClass(
                "th-game-transform-side th-game-transform th-game-focus-border th-game-transform-side"
              );
            });
          }
          instance.prev(".th-game-wrapper").animate(
            {
              scrollLeft: items[dragControl[gameKey].currentFirst].offsetLeft,
            },
            200,
            function () {
              if (!mq) {
                let nextGameToday = $(
                  items[dragControl[gameKey].currentFirst + 1]
                ).hasClass("th-game-today");
                if (nextGameToday) {
                  if (
                    instance.prev(".th-game-wrapper").hasClass("focus-scale")
                  ) {
                    $(items[dragControl[gameKey].currentFirst]).addClass(
                      "th-game-transform-side"
                    );
                    $(items[dragControl[gameKey].currentFirst + 1]).addClass(
                      "th-game-transform"
                    );
                    $(items[dragControl[gameKey].currentFirst + 2]).addClass(
                      "th-game-transform-side"
                    );
                  }
                  if (
                    instance.prev(".th-game-wrapper").hasClass("focus-border")
                  ) {
                    $(items[dragControl[gameKey].currentFirst + 1]).addClass(
                      "th-game-focus-border"
                    );
                  }
                }
              } else {
                let thisGameToday = $(
                  items[dragControl[gameKey].currentFirst]
                ).hasClass("th-game-today");
                if (thisGameToday) {
                  if (
                    instance.prev(".th-game-wrapper").hasClass("focus-border")
                  ) {
                    $(items[dragControl[gameKey].currentFirst]).addClass(
                      "th-game-focus-border"
                    );
                  }
                }
              }
              instance
                .prev(".th-game-wrapper")
                .scrollLeft(
                  items[dragControl[gameKey].currentFirst].offsetLeft
                );
              dragControl[gameKey].offsetDone = true;
            }
          );
        }
      }
    }
  }
});
