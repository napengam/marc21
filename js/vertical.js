function rotateHeadCell(tableId) {
    'use strict';

    // Locate the table based on the input tableId
    const table = typeof tableId === 'string' ? document.getElementById(tableId) : tableId;
    // If the table is not found, return early
    if (!table) {
        return;
    }
    table.style.visibility = 'hidden';
    // Loop through each header cell
    table.querySelectorAll('[data-rotate]').forEach(th => {
        // Create a div to wrap the content of the header cell
        const div = document.createElement('div');
        div.innerHTML = th.innerHTML;
        div.style.display = 'inline-block';


        // Clear the content of the header cell and append the content wrapper
        th.innerHTML = '';
        th.appendChild(div);

        // Store the initial height and width of the content wrapper
        const initialHeight = div.clientHeight;
        const initialWidth = div.clientWidth;

        // Apply rotation to the content wrapper
        div.style.transformOrigin = 'top left';
        div.style.transform = 'rotate(-90deg)';
        div.style.whiteSpace = 'nowrap';

        // Swap height and width to reflect the rotation
        div.style.width = initialHeight + 'px';
        div.style.height = initialWidth + 'px';

        div.style.position = 'relative';
        div.style.top = div.clientHeight + 'px';

        // Adjust vertical alignment for rotated content
        th.style.verticalAlign = 'bottom';

        // If the content wrapper contains an image, use flex display
        if (div.querySelector('img')) {
            div.style.display = 'flex';
        }
    });
    table.style.visibility = '';
}
