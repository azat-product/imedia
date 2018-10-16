function makeSortByRating() {
    var hash = '#search_list';

    if (document.location.search.indexOf('sort_by_rating=on') + 1 ) {
        if (document.location.search.indexOf('&sort_by_rating=on') + 1) {
            var search = document.location.search.replace(/&sort_by_rating=on/, '');
        } else {
            var search = document.location.search.replace(/sort_by_rating=on/, '');

            if (search === '?') {
                search = '';
            }
        }
    } else {
        var search = document.location.search === '' ? '?sort_by_rating=on' : (document.location.search + '&sort_by_rating=on');
    }

    document.location.href = document.location.origin + document.location.pathname + search + hash;
}