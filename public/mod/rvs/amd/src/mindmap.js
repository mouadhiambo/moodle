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
 * Mind map visualization module
 *
 * @module     mod_rvs/mindmap
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'], function($) {

    return {
        /**
         * Initialize the mind map visualization
         */
        init: function() {
            var container = document.getElementById('mindmap-visualization');
            if (!container) {
                return;
            }

            var mindmapData = container.getAttribute('data-mindmap');
            if (!mindmapData) {
                return;
            }

            try {
                var data = JSON.parse(mindmapData);
                this.renderMindMap(container, data);
            } catch (e) {
                console.error('Error parsing mind map data:', e);
            }
        },

        /**
         * Render the mind map
         *
         * @param {HTMLElement} container Container element
         * @param {Object} data Mind map data
         */
        renderMindMap: function(container, data) {
            // Simple HTML-based mind map rendering
            var html = '<div class="mindmap-root">';
            html += '<div class="mindmap-central">' + (data.central || 'Main Topic') + '</div>';
            html += '<div class="mindmap-branches">';

            if (data.branches && Array.isArray(data.branches)) {
                data.branches.forEach(function(branch) {
                    html += '<div class="mindmap-branch">';
                    html += '<div class="mindmap-branch-topic">' + (branch.topic || '') + '</div>';

                    if (branch.subtopics && Array.isArray(branch.subtopics)) {
                        html += '<ul class="mindmap-subtopics">';
                        branch.subtopics.forEach(function(subtopic) {
                            html += '<li>' + subtopic + '</li>';
                        });
                        html += '</ul>';
                    }

                    html += '</div>';
                });
            }

            html += '</div>';
            html += '</div>';

            container.innerHTML = html;

            // Add some basic styling
            this.addStyles();
        },

        /**
         * Add basic CSS styles for mind map
         */
        addStyles: function() {
            var styleId = 'rvs-mindmap-styles';
            if (document.getElementById(styleId)) {
                return;
            }

            var style = document.createElement('style');
            style.id = styleId;
            style.textContent = `
                .mindmap-root {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    padding: 20px;
                }
                .mindmap-central {
                    background: #0f6cbf;
                    color: white;
                    padding: 15px 30px;
                    border-radius: 50px;
                    font-size: 20px;
                    font-weight: bold;
                    margin-bottom: 30px;
                }
                .mindmap-branches {
                    display: flex;
                    flex-wrap: wrap;
                    justify-content: center;
                    gap: 20px;
                }
                .mindmap-branch {
                    background: #f0f0f0;
                    border: 2px solid #0f6cbf;
                    border-radius: 10px;
                    padding: 15px;
                    min-width: 200px;
                }
                .mindmap-branch-topic {
                    font-weight: bold;
                    color: #0f6cbf;
                    margin-bottom: 10px;
                    font-size: 16px;
                }
                .mindmap-subtopics {
                    list-style: none;
                    padding-left: 0;
                }
                .mindmap-subtopics li {
                    padding: 5px 0;
                    padding-left: 15px;
                    position: relative;
                }
                .mindmap-subtopics li:before {
                    content: "→";
                    position: absolute;
                    left: 0;
                }
            `;
            document.head.appendChild(style);
        }
    };
});

