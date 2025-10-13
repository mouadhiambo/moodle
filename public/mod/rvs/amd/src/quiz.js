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
 * Quiz module for RVS
 *
 * @module     mod_rvs/quiz
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'], function($) {

    var userAnswers = {};
    var filteredQuestions = [];

    return {
        /**
         * Initialize the quiz module
         */
        init: function() {
            if (typeof window.quizData === 'undefined') {
                return;
            }

            filteredQuestions = window.quizData;
            this.renderQuiz();
            this.setupEventHandlers();
        },

        /**
         * Render all quiz questions
         */
        renderQuiz: function() {
            var container = $('#quiz-questions');

            if (filteredQuestions.length === 0) {
                container.html('<div class="alert alert-info">No questions match the selected filter.</div>');
                return;
            }

            var html = '';

            filteredQuestions.forEach(function(question) {
                html += '<div class="quiz-question card mb-3" data-question-id="' + question.id + '">';
                html += '<div class="card-body">';

                // Question header
                html += '<div class="d-flex justify-content-between align-items-start mb-3">';
                html += '<h5 class="mb-0">Question ' + question.number + '</h5>';
                html += '<span class="badge badge-' + this.getDifficultyClass(question.difficulty) + '">';
                html += question.difficulty.toUpperCase();
                html += '</span>';
                html += '</div>';

                // Question text
                html += '<p class="question-text">' + question.question + '</p>';

                // Options
                html += '<div class="quiz-options">';
                question.options.forEach(function(option, optIndex) {
                    html += '<div class="form-check">';
                    html += '<input class="form-check-input quiz-option" type="radio" ';
                    html += 'name="question_' + question.id + '" ';
                    html += 'id="q' + question.id + '_opt' + optIndex + '" ';
                    html += 'value="' + optIndex + '">';
                    html += '<label class="form-check-label" for="q' + question.id + '_opt' + optIndex + '">';
                    html += option;
                    html += '</label>';
                    html += '</div>';
                });
                html += '</div>';

                // Explanation (hidden initially)
                html += '<div class="quiz-explanation alert alert-info mt-3" style="display: none;">';
                html += '<strong>Explanation:</strong> ' + question.explanation;
                html += '</div>';

                html += '</div>';
                html += '</div>';
            }.bind(this));

            container.html(html);
            this.addStyles();
        },

        /**
         * Get Bootstrap class for difficulty level
         *
         * @param {String} difficulty Difficulty level
         * @returns {String} CSS class
         */
        getDifficultyClass: function(difficulty) {
            switch (difficulty) {
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

            // Store user answers
            $('.quiz-option').on('change', function() {
                var questionId = $(this).closest('.quiz-question').data('question-id');
                userAnswers[questionId] = parseInt($(this).val());
            });

            // Check answers button
            $('#check-answers').on('click', function() {
                self.checkAnswers();
            });

            // Reset quiz button
            $('#reset-quiz').on('click', function() {
                self.resetQuiz();
            });

            // Difficulty filter
            $('#quiz-difficulty-filter').on('change', function() {
                var difficulty = $(this).val();

                if (difficulty === 'all') {
                    filteredQuestions = window.quizData;
                } else {
                    filteredQuestions = window.quizData.filter(function(q) {
                        return q.difficulty === difficulty;
                    });
                }

                userAnswers = {};
                self.renderQuiz();
                self.setupEventHandlers();
            });
        },

        /**
         * Check all answers and display results
         */
        checkAnswers: function() {
            var correct = 0;
            var total = filteredQuestions.length;

            filteredQuestions.forEach(function(question) {
                var questionCard = $('.quiz-question[data-question-id="' + question.id + '"]');
                var userAnswer = userAnswers[question.id];

                // Reset previous feedback
                questionCard.find('.quiz-options .form-check').removeClass('correct-answer incorrect-answer');

                if (typeof userAnswer !== 'undefined') {
                    if (userAnswer === question.correctanswer) {
                        correct++;
                        questionCard.find('.quiz-options .form-check').eq(userAnswer)
                            .addClass('correct-answer');
                    } else {
                        questionCard.find('.quiz-options .form-check').eq(userAnswer)
                            .addClass('incorrect-answer');
                        questionCard.find('.quiz-options .form-check').eq(question.correctanswer)
                            .addClass('correct-answer');
                    }

                    // Show explanation
                    questionCard.find('.quiz-explanation').slideDown();
                } else {
                    // Show correct answer for unanswered questions
                    questionCard.find('.quiz-options .form-check').eq(question.correctanswer)
                        .addClass('correct-answer');
                }
            });

            var percentage = total > 0 ? Math.round((correct / total) * 100) : 0;
            var resultHtml = '<div class="alert alert-' + this.getScoreClass(percentage) + '">';
            resultHtml += '<h4>Your Score: ' + correct + ' / ' + total + ' (' + percentage + '%)</h4>';

            if (percentage >= 80) {
                resultHtml += '<p>Excellent work! You have a strong understanding of the material.</p>';
            } else if (percentage >= 60) {
                resultHtml += '<p>Good job! Review the explanations for questions you missed.</p>';
            } else {
                resultHtml += '<p>Keep studying! Review the material and try again.</p>';
            }

            resultHtml += '</div>';

            $('#quiz-results').html(resultHtml).slideDown();
        },

        /**
         * Get alert class based on score
         *
         * @param {Number} percentage Score percentage
         * @returns {String} CSS class
         */
        getScoreClass: function(percentage) {
            if (percentage >= 80) {
                return 'success';
            } else if (percentage >= 60) {
                return 'warning';
            } else {
                return 'danger';
            }
        },

        /**
         * Reset the quiz
         */
        resetQuiz: function() {
            userAnswers = {};
            $('.quiz-option').prop('checked', false);
            $('.quiz-options .form-check').removeClass('correct-answer incorrect-answer');
            $('.quiz-explanation').slideUp();
            $('#quiz-results').slideUp();
        },

        /**
         * Add CSS styles for quiz
         */
        addStyles: function() {
            var styleId = 'rvs-quiz-styles';
            if (document.getElementById(styleId)) {
                return;
            }

            var style = document.createElement('style');
            style.id = styleId;
            style.textContent = `
                .quiz-question {
                    border-left: 4px solid #0f6cbf;
                }
                .quiz-options .form-check {
                    padding: 10px;
                    margin: 5px 0;
                    border-radius: 5px;
                    transition: background-color 0.3s;
                }
                .quiz-options .form-check:hover {
                    background-color: #f8f9fa;
                }
                .quiz-options .form-check.correct-answer {
                    background-color: #d4edda;
                    border: 1px solid #c3e6cb;
                }
                .quiz-options .form-check.incorrect-answer {
                    background-color: #f8d7da;
                    border: 1px solid #f5c6cb;
                }
                .quiz-options .form-check.correct-answer label {
                    color: #155724;
                    font-weight: bold;
                }
                .quiz-options .form-check.incorrect-answer label {
                    color: #721c24;
                }
                .question-text {
                    font-size: 16px;
                    line-height: 1.6;
                    margin-bottom: 15px;
                }
                .quiz-explanation {
                    margin-top: 15px;
                }
            `;
            document.head.appendChild(style);
        }
    };
});

