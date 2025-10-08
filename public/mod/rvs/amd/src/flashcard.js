// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Flashcard module for RVS
 *
 * @module     mod_rvs/flashcard
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'], function($) {

    var currentCard = 0;
    var filteredCards = [];
    var isFlipped = false;

    return {
        /**
         * Initialize the flashcard module
         */
        init: function() {
            if (typeof window.flashcardData === 'undefined') {
                return;
            }

            filteredCards = window.flashcardData;
            this.renderCurrentCard();
            this.setupEventHandlers();
        },

        /**
         * Render the current flashcard
         */
        renderCurrentCard: function() {
            var container = $('#flashcard-deck');
            
            if (filteredCards.length === 0) {
                container.html('<div class="alert alert-info">No flashcards match the selected filter.</div>');
                return;
            }

            var card = filteredCards[currentCard];
            
            var html = '<div class="flashcard-wrapper">';
            html += '<div class="flashcard' + (isFlipped ? ' flipped' : '') + '">';
            html += '<div class="flashcard-front">';
            html += '<div class="flashcard-label">Question</div>';
            html += '<div class="flashcard-content">' + card.question + '</div>';
            html += '<div class="flashcard-difficulty badge badge-' + this.getDifficultyClass(card.difficulty) + '">';
            html += card.difficulty.toUpperCase();
            html += '</div>';
            html += '</div>';
            html += '<div class="flashcard-back">';
            html += '<div class="flashcard-label">Answer</div>';
            html += '<div class="flashcard-content">' + card.answer + '</div>';
            html += '</div>';
            html += '</div>';
            html += '</div>';

            container.html(html);

            // Update counter
            $('#flashcard-counter').text((currentCard + 1) + ' / ' + filteredCards.length);

            // Add styles
            this.addStyles();
        },

        /**
         * Get Bootstrap class for difficulty level
         *
         * @param {String} difficulty Difficulty level
         * @returns {String} CSS class
         */
        getDifficultyClass: function(difficulty) {
            switch(difficulty) {
                case 'easy':
                    return 'success';
                case 'medium':
                    return 'warning';
                case 'hard':
                    return 'danger';
                default:
                    return 'secondary';
            }
        },

        /**
         * Setup event handlers
         */
        setupEventHandlers: function() {
            var self = this;

            // Previous button
            $('#flashcard-prev').on('click', function() {
                if (currentCard > 0) {
                    currentCard--;
                    isFlipped = false;
                    self.renderCurrentCard();
                }
            });

            // Next button
            $('#flashcard-next').on('click', function() {
                if (currentCard < filteredCards.length - 1) {
                    currentCard++;
                    isFlipped = false;
                    self.renderCurrentCard();
                }
            });

            // Flip button
            $('#flashcard-flip').on('click', function() {
                isFlipped = !isFlipped;
                $('.flashcard').toggleClass('flipped');
            });

            // Difficulty filter
            $('#flashcard-difficulty-filter').on('change', function() {
                var difficulty = $(this).val();
                
                if (difficulty === 'all') {
                    filteredCards = window.flashcardData;
                } else {
                    filteredCards = window.flashcardData.filter(function(card) {
                        return card.difficulty === difficulty;
                    });
                }

                currentCard = 0;
                isFlipped = false;
                self.renderCurrentCard();
            });
        },

        /**
         * Add CSS styles for flashcards
         */
        addStyles: function() {
            var styleId = 'rvs-flashcard-styles';
            if (document.getElementById(styleId)) {
                return;
            }

            var style = document.createElement('style');
            style.id = styleId;
            style.textContent = `
                .flashcard-wrapper {
                    perspective: 1000px;
                    margin: 20px auto;
                    max-width: 600px;
                }
                .flashcard {
                    position: relative;
                    width: 100%;
                    height: 400px;
                    transition: transform 0.6s;
                    transform-style: preserve-3d;
                }
                .flashcard.flipped {
                    transform: rotateY(180deg);
                }
                .flashcard-front,
                .flashcard-back {
                    position: absolute;
                    width: 100%;
                    height: 100%;
                    backface-visibility: hidden;
                    display: flex;
                    flex-direction: column;
                    justify-content: center;
                    align-items: center;
                    border: 2px solid #0f6cbf;
                    border-radius: 10px;
                    background: white;
                    padding: 30px;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                }
                .flashcard-back {
                    transform: rotateY(180deg);
                    background: #f8f9fa;
                }
                .flashcard-label {
                    position: absolute;
                    top: 15px;
                    left: 15px;
                    font-size: 12px;
                    color: #666;
                    text-transform: uppercase;
                    font-weight: bold;
                }
                .flashcard-content {
                    font-size: 18px;
                    text-align: center;
                    line-height: 1.6;
                }
                .flashcard-difficulty {
                    position: absolute;
                    top: 15px;
                    right: 15px;
                    font-size: 11px;
                    padding: 4px 10px;
                }
                .flashcard-navigation {
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    gap: 10px;
                }
            `;
            document.head.appendChild(style);
        }
    };
});

