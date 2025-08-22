/**
 * Admin Meta Boxes JavaScript
 * Handles functionality for admin meta boxes
 */

(function($) {
    'use strict';

    /**
     * Initialize closure management for luoghi meta box
     */
    function initClosureManagement() {
        const $wrap = $('#yht-chiusure-wrap');
        if (!$wrap.length) return;

        let data = [];
        try { 
            data = JSON.parse($wrap.data('json') || '[]'); 
        } catch(e) { 
            data = []; 
        }

        function render() {
            const $body = $('#yht-chiusure-body'); 
            $body.empty();
            
            data.forEach((row, i) => {
                const tr = $(`
                    <tr>
                        <td><input type="date" value="${row.start || ''}" class="yht-c-start"/></td>
                        <td><input type="date" value="${row.end || ''}" class="yht-c-end"/></td>
                        <td><input type="text" value="${row.note || ''}" class="yht-c-note"/></td>
                        <td><a href="#" data-i="${i}" class="yht-c-del">Rimuovi</a></td>
                    </tr>`);
                $body.append(tr);
            });
            
            $('#yht_chiusure_json').val(JSON.stringify(data));
        }

        // Initial render
        render();

        // Add new closure
        $('#yht-add-closure').on('click', function(e) {
            e.preventDefault();
            data.push({start: '', end: '', note: ''}); 
            render();
        });

        // Update data on input changes
        $(document).on('input', '.yht-c-start', function() { 
            const index = $(this).closest('tr').index();
            data[index].start = this.value; 
            $('#yht_chiusure_json').val(JSON.stringify(data)); 
        });

        $(document).on('input', '.yht-c-end', function() { 
            const index = $(this).closest('tr').index();
            data[index].end = this.value; 
            $('#yht_chiusure_json').val(JSON.stringify(data)); 
        });

        $(document).on('input', '.yht-c-note', function() { 
            const index = $(this).closest('tr').index();
            data[index].note = this.value; 
            $('#yht_chiusure_json').val(JSON.stringify(data)); 
        });

        // Delete closure
        $(document).on('click', '.yht-c-del', function(e) { 
            e.preventDefault(); 
            const index = $(this).data('i');
            data.splice(index, 1); 
            render(); 
        });
    }

    // Initialize when document ready
    $(document).ready(function() {
        initClosureManagement();
    });

})(jQuery);