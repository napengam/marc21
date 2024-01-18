function makeSticky(id, config = null) {
    var theTable, innerHTML, body, styleElem;
    
    styleElem = document.createElement('STYLE');
    // *****************************************
    // table given by id or direct as object
    // ******************************************

    if (typeof id === 'string') {
        body = document.getElementById(id).tBodies[0];
        theTable = document.getElementById(id);
    } else if (typeof id === 'object') {
        body = id.tBodies[0];
        theTable = id;
        id = theTable.id;
    }
    if (body === null || id === '' || theTable.querySelector('thead') === null) {
        return;
    }
    theTable.dataset.stickycols = 0;
    // *****************************************
    // merge given config with default.
    // This way we have allways a config
    // ******************************************
    config = Object.assign({}, {'col': 0, 'loff': 0, 'toff': 0}, config);

    config.toff = getConfig(config.toff, 'clientHeight');
    config.loff = getConfig(config.loff, 'clientWidth');
    // *****************************************
    // create style sheet
    // ******************************************

    innerHTML = `
            #${id} thead  {
                position: sticky;
                top:${config.toff}px;
                z-index: 4;
            }`;

    if (config.col > 0) {
        theTable.dataset.stickycols = config.col; // as info
        let l = 0;
        if (config.loff) {
            l = config.loff;
        }
        for (let i = 0; i < config.col; i++) {
            innerHTML += `
            #${id} td:nth-child(${i + 1}) {
                position: sticky;
                left: ${l}px;
                z-index: 3;               
            }
            #${id} th:nth-child(${i + 1}){
                position: sticky;
                left: ${l}px;
                z-index: 3;              
            }
          `;
            l += body.rows[0].cells[i].clientWidth;
        }
    }
    styleElem.insertAdjacentHTML('beforeend', innerHTML);
    document.getElementsByTagName('head')[0].appendChild(styleElem);

    // *****************************************
    // remove stickyness for header cells that
    // are out of scope because of colspan etc
    // ******************************************      
    removeSticky(theTable, config.col);
    return styleElem;

    // *****************************************
    // get vaues 
    // ******************************************

    function getConfig(configAttrib, what) {
        let value = 0, rect = null, add = 0;

        if (typeof configAttrib === 'string' && configAttrib !== '') {
            let obj = document.getElementById(configAttrib);
            if (obj) {
                rect = obj.getBoundingClientRect();
                value = obj[what];
            }
        } else if (typeof configAttrib === 'object') {
            rect = configAttrib.getBoundingClientRect();
            value = configAttrib[what];
        } else if (typeof configAttrib === 'number') {
            value = configAttrib;
        }

        if (rect !== null) {
            if (what === 'clientWidth') {
                add = rect.left;
            } else if (what === 'clientHeight') {
                add = rect.top;
            }
        }
        return value + add;
    }
    // *****************************************
    // remove stickyness from not related header
    // cells 
    // ******************************************

    function removeSticky(theTable, col) {

        if (col <= 0) {
            return;
        }
        [].forEach.call(theTable.tHead.rows, (row) => {
            [].forEach.call(row.cells, (cell) => {
                if (cell.style.position === 'sticky' && (cell.colSpan > col || cell.cellIndex >= col)) {
                    cell.style.position = 'static';
                }
            });
        }
        );
}
}
