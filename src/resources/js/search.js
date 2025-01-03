// Search bar
document.querySelector('.search').addEventListener('click', function (e) {
    e.preventDefault();
    const searchContainer = this.closest('.search-container');
    searchContainer.classList.toggle('active');
    document.querySelectorAll('nav ul li').forEach(function (li) {
        if (!(li.classList.contains('search-container'))) {
            li.classList.toggle('hide-item');
        }
    });
    window.scrollTo(0, 0);
    setTimeout(function() {document.getElementById('input-search').focus();}, 500);
});

document.querySelector('.close-search').addEventListener('click', function (e) {
    e.preventDefault();
    const searchContainer = this.closest('.search-container');
    searchContainer.classList.remove('active');

    document.querySelectorAll('nav ul li').forEach(function (li) {
        if (!li.classList.contains('search-container')) {
            li.classList.toggle('hide-item');
        }
    });
});

// Funzione per aggiornare i risultati nel DOM
function updateResults(results) {
    const resultsList = document.getElementById('sec-search_content');
    fetch('views/components/cards/vinyl.php')
    .then(response => {
        if (!response.ok) {
            throw new Error(`Errore nel caricamento del template: ${response.status}`);
        }
        return response.text();
    })
    .then(templateHTML => {
        // Inserire il contenuto del template direttamente nel DOM nascosto
        document.body.insertAdjacentHTML("beforeend", templateHTML);

        // Recuperare il template appena aggiunto
        const template = document.getElementById("search-vinyl");

        // Se non ci sono risultati
        if (results.message.length !== 0) {
            clear();
            Array.prototype.forEach.call(results.message, result => {
                const clone = template.content.cloneNode(true);
                clone.querySelector(".vinyl-cover").src = "/resources/img/albums/" + result.cover;
                clone.querySelector(".vinyl-preview").href = "/vinyl?id=" + result.id_vinyl;
                clone.querySelector(".vinyl-title").textContent = result.title;
                clone.querySelector(".vinyl-title").title = result.title;
                clone.querySelector(".vinyl-artist").textContent = result.artist;
                clone.querySelector(".vinyl-genre").textContent = "#" + result.genre;
                clone.querySelector(".add-cart").textContent = "Add to cart - €" + result.cost;
                clone.querySelector(".add-cart").onclick = function() {
                    addToCart(result.id_vinyl, 1);
                };
                resultsList.appendChild(clone);
            });
        }
    })
}

function clear() {
    document.getElementById('sec-search_content').innerHTML = ''; // Svuota i risultati precedenti
}

document.getElementById('btn-search_close').addEventListener('click', function() {
    document.getElementById('main-content').ariaHidden = 'false';
    document.getElementById('sec-search_content').ariaHidden = 'true';
    document.getElementById('main-content').style = 'display: block';
    document.getElementById('input-search').value = "";
    document.getElementById('sec-search_content').style = 'display: none';
});

function search() {
    const filter = document.getElementById('select-search_filter');
    if (document.getElementById('input-search').value !== '') {
        fetch(`/search?${filter.value}=${encodeURIComponent(document.getElementById('input-search').value)}`)
        .then(async (response) => {
            if (response.ok) {
                return await response.json();
            } else {
                throw new Error("Server response was not ok");
            }
        })
        .then(async (data) => {
            document.getElementById('main-content').ariaHidden = 'true';
            document.getElementById('sec-search_content').ariaHidden = 'false';
            document.getElementById('main-content').style = 'display: none';
            document.getElementById('sec-search_content').style = 'display: flex';
            await updateResults(data);
        });
    } else {
        clear();
    }
}

document.getElementById('select-search_filter').addEventListener('change', search);
document.getElementById('input-search').addEventListener('input', search);