<nav aria-label="Main navigation">
    <ul>
        <li>
            <a href="/">
                <div class="vinyl"></div>
                <p>Shop</p>
            </a>
        </li>
        <li class="search-container">
            <button id="btn-search" class="search">
                <i class="bi bi-search"></i>
                <p>Search</p>
            </button>
            
            <div class="search-bar">
                <label for="input-search" class="sr-only">Search</label>
                <input id="input-search" name="input-search" type="text" placeholder="Search...">

                <div class="select-wrapper">
                    <label for="search_filter" class="sr-only">Search in</label>
                    <span class="label" aria-hidden="true">in</span>
                    <select id="select-search_filter" name="search_filter" aria-label="Search in">
                        <option value="album">album</option>
                        <option value="artist">artist</option>
                        <option value="genre">genre</option>
                        <option value="track">track</option>
                    </select>
                </div>
                
                <button class="close-search" id="btn-search_close" aria-label="Close search">
                    <i class="bi bi-x"></i>
                </button>
            </div>
        </li>
        <li>
            <a href="/cart">
                <i class="bi bi-bag-fill"></i>
                <p>Cart</p>
            </a>
        </li>
        <li>
            <a href="/user">
                <i class="bi bi-person-fill"></i>
                <p>User</p>
            </a>
        </li>
    </ul>
</nav>