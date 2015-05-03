
(function($){
	$.fn.crossword = function(entryData) {

			var puzz = {}; // put data array in object literal to namespace it into safety
			puzz.data = entryData;

			// append clues markup after puzzle wrapper div
			// This should be moved into a configuration object
			this.after('<div id="puzzle-clues"><h2>Across</h2><ul id="across"></ul><h2>Down</h2><ul id="down"></ul></div>');

			// initialize some variables
			var tbl = ['<table id="puzzle">'],
			    puzzEl = this,
				clues = $('#puzzle-clues'),
				clueLiEls,
				coords,
				entryCount = puzz.data.length,
				entries = [],
				rows = [],
				cols = [],
				solved = [],
				tabindex,
				$actives,
				activePosition = 0,
				activeClueIndex = 0,
				currOri,
				targetInput,
				mode = 'interacting',
				solvedToggle = false,
				z = 0;

			var puzInit = {

				init: function() {
					currOri = 'across'; // app's init orientation could move to config object

					// Reorder the problems array ascending by POSITION
					puzz.data.sort(function(a,b) {
						return a.position - b.position;
					});

					// Set keyup handlers for the 'entry' inputs
					puzzEl.delegate('input', 'keyup', function(e){
						mode = 'interacting';


						// need to figure out orientation up front, before we attempt to highlight an entry
						switch(e.which) {
							case 39:
							case 37:
								currOri = 'across';
								break;
							case 38:
							case 40:
								currOri = 'down';
								break;
							default:
								break;
						}

						// alt and shift
						if(e.keyCode == 16 || e.keyCode == 17 || e.keyCode == 18)
							return false;


						if ( e.keyCode === 9 ) {
							return false;
						} else if (
							e.keyCode === 37 ||
							e.keyCode === 38 ||
							e.keyCode === 39 ||
							e.keyCode === 40 ||
							e.keyCode === 8 ||
							e.keyCode === 46 ) {



							if (e.keyCode === 8 || e.keyCode === 46) {
								currOri === 'across' ? nav.nextPrevNav(e, 37) : nav.nextPrevNav(e, 38);
							} else {
								nav.nextPrevNav(e);
							}

							e.preventDefault();
							return false;
						} else {

							puzInit.checkAnswer(e);
						}

						e.preventDefault();
						return false;
					});

					// tab navigation handler setup
					puzzEl.delegate('input', 'keydown', function(e) {

						if ( e.keyCode === 9) {

							mode = "setting ui";
							if (solvedToggle) solvedToggle = false;

							//puzInit.checkAnswer(e)
							nav.updateByEntry(e);

						} else {
							return true;
						}

						e.preventDefault();

					});

					// tab navigation handler setup
					puzzEl.delegate('input', 'click', function(e) {
						mode = "setting ui";
						if (solvedToggle) solvedToggle = false;

						nav.updateByEntry(e);
						e.preventDefault();

					});


					// click/tab clues 'navigation' handler setup
					clues.delegate('li', 'click', function(e) {
						mode = 'setting ui';

						if (!e.keyCode) {
							nav.updateByNav(e);
						}
						e.preventDefault();
					});


					// highlight the letter in selected 'light' - better ux than making user highlight letter with second action
					puzzEl.delegate('#puzzle', 'click', function(e) {
						$(e.target).focus();
						$(e.target).select();
					});

					// DELETE FOR BG
					puzInit.calcCoords();

					// Puzzle clues added to DOM in calcCoords(), so now immediately put mouse focus on first clue
					clueLiEls = $('#puzzle-clues li');
					$('#' + currOri + ' li' ).eq(0).addClass('clues-active').focus();

					// DELETE FOR BG
					puzInit.buildTable();
					puzInit.buildEntries();

				},

				/*
					- Given beginning coordinates, calculate all coordinates for entries, puts them into entries array
					- Builds clue markup and puts screen focus on the first one
				*/
				calcCoords: function() {
					/*
						Calculate all puzzle entry coordinates, put into entries array
					*/
					for (var i = 0, p = entryCount; i < p; ++i) {
						// set up array of coordinates for each problem
						entries.push(i);
						entries[i] = [];

						for (var x=0, j = puzz.data[i].answer.length; x < j; ++x) {
							entries[i].push(x);
							coords = puzz.data[i].orientation === 'across' ? "" + puzz.data[i].startx++ + "," + puzz.data[i].starty + "" : "" + puzz.data[i].startx + "," + puzz.data[i].starty++ + "" ;
							entries[i][x] = coords;
						}

						// while we're in here, add clues to DOM!
						$('#' + puzz.data[i].orientation).append('<li tabindex="1" data-position="' + i + '">' + puzz.data[i].position + ". " + puzz.data[i].clue + '<a href="#" style="float: right;" class="enabled-link" onclick="$(this).text($(this).attr(\'data\')).removeClass(\'enabled-link\').addClass(\'disable-link\')" data="' + puzz.data[i].help + '">Pomoc</a></li>');
					}

					// Calculate rows/cols by finding max coords of each entry, then picking the highest
					for (var i = 0, p = entryCount; i < p; ++i) {
						for (var x=0; x < entries[i].length; x++) {
							cols.push(entries[i][x].split(',')[0]);
							rows.push(entries[i][x].split(',')[1]);
						};
					}

					rows = Math.max.apply(Math, rows) + "";
					cols = Math.max.apply(Math, cols) + "";

				},

				/*
					Build the table markup
					- adds [data-coords] to each <td> cell
				*/
				buildTable: function() {
					for (var i=1; i <= rows; ++i) {
						tbl.push("<tr>");
							for (var x=1; x <= cols; ++x) {
								tbl.push('<td data-coords="' + x + ',' + i + '"></td>');
							};
						tbl.push("</tr>");
					};

					tbl.push("</table>");
					puzzEl.append(tbl.join(''));
				},

				/*
					Builds entries into table
					- Adds entry class(es) to <td> cells
					- Adds tabindexes to <inputs>
				*/
				buildEntries: function() {
					var puzzCells = $('#puzzle td'),
						light,
						$groupedLights,
						hasOffset = false,
						positionOffset = entryCount - puzz.data[puzz.data.length-1].position; // diff. between total ENTRIES and highest POSITIONS

					for (var x=1, p = entryCount; x <= p; ++x) {
						var letters = puzz.data[x-1].answer.split('');

						for (var i=0; i < entries[x-1].length; ++i) {
							light = $(puzzCells +'[data-coords="' + entries[x-1][i] + '"]');

							// check if POSITION property of the entry on current go-round is same as previous.
							// If so, it means there's an across & down entry for the position.
							// Therefore you need to subtract the offset when applying the entry class.
							if(x > 1 ){
								if (puzz.data[x-1].position === puzz.data[x-2].position) {
									hasOffset = true;
								};
							}

							if($(light).empty()){
								$(light)
									.addClass('entry-' + (hasOffset ? x - positionOffset : x) + ' position-' + (x-1) )
									.append('<input maxlength="1" val="" type="text" tabindex="-1" />');
							}
						};

					};

					// Put entry number in first 'light' of each entry, skipping it if already present
					for (var i=1, p = entryCount; i <= p; ++i) {
						$groupedLights = $('.entry-' + i);
						if(!$('.entry-' + i +':eq(0) span').length){
							$groupedLights.eq(0)
								.append('<span>' + puzz.data[i-1].position + '</span>');
						} else {
							var tmp = $('.entry-' + i +':eq(0) span').text();
							var text = puzz.data[i-1].orientation == 'across' ? tmp + ' / ' + puzz.data[i-1].position : puzz.data[i-1].position + ' / ' + tmp;
							$('.entry-' + i +':eq(0) span').text(text);
						}
					}

					util.highlightEntry();
					util.highlightClue();
					$('.active').eq(0).focus();
					$('.active').eq(0).select();

				},/*

				escapeDiacritics: function (str) {
			        return str.replace(/ą/g, 'a').replace(/Ą/g, 'A')
			            .replace(/ć/g, 'c').replace(/Ć/g, 'C')
			            .replace(/ę/g, 'e').replace(/Ę/g, 'E')
			            .replace(/ł/g, 'l').replace(/Ł/g, 'L')
			            .replace(/ń/g, 'n').replace(/Ń/g, 'N')
			            .replace(/ó/g, 'o').replace(/Ó/g, 'O')
			            .replace(/ś/g, 's').replace(/Ś/g, 'S')
			            .replace(/ż/g, 'z').replace(/Ż/g, 'Z')
			            .replace(/ź/g, 'z').replace(/Ź/g, 'Z');
			    },*/


				/*
					- Checks current entry input group value against answer
					- If not complete, auto-selects next input for user
				*/
				checkAnswer: function(e) {

					var valToCheck, currVal;

					util.getActivePositionFromClassGroup($(e.target));

					valToCheck = puzz.data[activePosition].answer.toLowerCase();

					$('.position-' + activePosition + ' input')
						.map(function() {
					  		return $(this)
								.val($(this).val().toUpperCase());
						});

					currVal = $('.position-' + activePosition + ' input')
						.map(function() {
					  		return $(this)
								.val()
								.toLowerCase();
						})
						.get()
						.join('');

					//console.log(currVal + " " + valToCheck);
					if(valToCheck === currVal){
						$('.active')
							.addClass('done')
							.removeClass('active')
							.blur();

						$('.clues-active').addClass('clue-done');

                        var audio = new Audio('//sowkomat/public/asystent/krzyzowka/php/speech.php?ie=UTF-8&q=' + currVal + '&tl=' + jsAudioLanguage);
                        console.log(audio);
                        audio.play();

						solved.push(valToCheck);
						solvedToggle = true;

						if($('li:not([class="clue-done"])').length - $('li.clues-active').length == 0) {
							var ask = confirm('Congratulations! Click OK to generate a new crossword or Cancel to stay on the current page!');
							if(true === ask)
								location.reload();
						}

						return;
					}

					currOri === 'across' ? nav.nextPrevNav(e, 39) : nav.nextPrevNav(e, 40);


					//z++;
					//console.log(z);
					//console.log('checkAnswer() solvedToggle: '+solvedToggle);

				}


			}; // end puzInit object


			var nav = {

				nextPrevNav: function(e, override) {
					var len = $actives.length,
						struck = override ? override : e.which,
						el = $(e.target),
						p = el.parent(),
						ps = el.parents(),
						selector;

					util.getActivePositionFromClassGroup(el);
					util.highlightEntry();
					util.highlightClue();

					$('.current').removeClass('current');

					selector = '.position-' + activePosition + ' input';

					// move input focus/select to 'next' input

					// changed by Alex start
					switch(struck) {
						case 39:
							if(p.next().find('input').hasClass('done'))
								p
									.next().next()
									.find('input')
									.addClass('current')
									.select();
							else
								p
									.next()
									.find('input')
									.addClass('current')
									.select();

							break;

						case 37:
							if(p.prev().find('input').hasClass('done'))
								p
									.prev().prev()
									.find('input')
									.addClass('current')
									.select();
							else
								p
									.prev()
									.find('input')
									.addClass('current')
									.select();

							break;

						case 40:
							if(ps.next('tr').find(selector).hasClass('done'))
								ps
									.next('tr').next()
									.find(selector)
									.addClass('current')
									.select();
							else
								ps
									.next('tr')
									.find(selector)
									.addClass('current')
									.select();

							break;

						case 38:
							if(ps.prev('tr').find(selector).hasClass('done'))
								ps
									.prev('tr').prev()
									.find(selector)
									.addClass('current')
									.select();
							else
								ps
									.prev('tr')
									.find(selector)
									.addClass('current')
									.select();

							break;

						default:
						break;
					}
					// changed by Alex start

				},

				updateByNav: function(e) {
					var target;

					$('.clues-active').removeClass('clues-active');
					$('.active').removeClass('active');
					$('.current').removeClass('current');
					currIndex = 0;

					target = e.target;
					activePosition = $(e.target).data('position');

					util.highlightEntry();
					util.highlightClue();



					if($('.active').eq(0).hasClass('done')) {
						$('.active').eq(1).focus();
						$('.active').eq(1).select();
					} else {
						$('.active').eq(0).focus();
						$('.active').eq(0).select();
					}




					// store orientation for 'smart' auto-selecting next input
					currOri = $('.clues-active').parent('ul').prop('id');

					activeClueIndex = $(clueLiEls).index(e.target);
					//console.log('updateByNav() activeClueIndex: '+activeClueIndex);


				},

				// Sets activePosition var and adds active class to current entry
				updateByEntry: function(e, next) {
					var classes, next, clue, e1Ori, e2Ori, e1Cell, e2Cell;

					if(e.keyCode === 9 || next){
						// handle tabbing through problems, which keys off clues and requires different handling
						activeClueIndex = activeClueIndex === clueLiEls.length-1 ? 0 : ++activeClueIndex;

						$('.clues-active').removeClass('.clues-active');

						next = $(clueLiEls[activeClueIndex]);
						currOri = next.parent().prop('id');
						activePosition = $(next).data('position');

						// skips over already-solved problems
						util.getSkips(activeClueIndex);
						activePosition = $(clueLiEls[activeClueIndex]).data('position');


					} else {
						activeClueIndex = activeClueIndex === clueLiEls.length-1 ? 0 : ++activeClueIndex;

						util.getActivePositionFromClassGroup(e.target);

						clue = $(clueLiEls + '[data-position=' + activePosition + ']');
						activeClueIndex = $(clueLiEls).index(clue);

						currOri = clue.parent().prop('id');

					}

						util.highlightEntry();
						util.highlightClue();


						if($('.active').eq(0).hasClass('done')) {
							$('.active').eq(1).focus();
							$('.active').eq(1).select();
						} else {
							$('.active').eq(0).focus();
							$('.active').eq(0).select();
						}

				}

			}; // end nav object


			var util = {
				highlightEntry: function() {
					// this routine needs to be smarter because it doesn't need to fire every time, only
					// when activePosition changes
					$actives = $('.active');
					$actives.removeClass('active');
					$actives = $('.position-' + activePosition + ' input').addClass('active');

				},

				highlightClue: function() {
					var clue;
					$('.clues-active').removeClass('clues-active');
					$(clueLiEls + '[data-position=' + activePosition + ']').addClass('clues-active');

					if (mode === 'interacting') {
						clue = $(clueLiEls + '[data-position=' + activePosition + ']');
						activeClueIndex = $(clueLiEls).index(clue);
					};
				},

				getClasses: function(light, type) {
					if (!light.length) return false;

					var classes = $(light).prop('class').split(' '),
					classLen = classes.length,
					positions = [];

					// pluck out just the position classes
					for(var i=0; i < classLen; ++i){
						if (!classes[i].indexOf(type) ) {
							positions.push(classes[i]);
						}
					}

					return positions;
				},

				getActivePositionFromClassGroup: function(el){

						classes = util.getClasses($(el).parent(), 'position');

						if(classes.length > 1){
							// get orientation for each reported position
							e1Ori = $(clueLiEls + '[data-position=' + classes[0].split('-')[1] + ']').parent().prop('id');
							e2Ori = $(clueLiEls + '[data-position=' + classes[1].split('-')[1] + ']').parent().prop('id');

							// test if clicked input is first in series. If so, and it intersects with
							// entry of opposite orientation, switch to select this one instead
							e1Cell = $('.position-' + classes[0].split('-')[1] + ' input').index(el);
							e2Cell = $('.position-' + classes[1].split('-')[1] + ' input').index(el);

							if(mode === "setting ui"){
								currOri = e1Cell === 0 ? e1Ori : e2Ori; // change orientation if cell clicked was first in a entry of opposite direction
							}

							if(e1Ori === currOri){
								activePosition = classes[0].split('-')[1];
							} else if(e2Ori === currOri){
								activePosition = classes[1].split('-')[1];
							}
						} else {
							activePosition = classes[0].split('-')[1];
						}


				},

				checkSolved: function(valToCheck) {
					for (var i=0, s=solved.length; i < s; i++) {
						if(valToCheck === solved[i]){
							return true;
						}

					}
				},

				getSkips: function(index) {

					if ($(clueLiEls[index]).hasClass('clue-done')){
						activeClueIndex = index === clueLiEls.length-1 ? 0 : ++activeClueIndex;
						util.getSkips(activeClueIndex);
					} else {
						return false;
					}
				}

			}; // end util object


			puzInit.init();


	}

})(jQuery);