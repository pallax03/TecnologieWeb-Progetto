<form id="form-vinyl_add">
    <h3>Vinyl Info</h3>
    <ul>
        <li>
            <fieldset>
                <legend>Type:</legend>
                <label for="radio-type_LP">
                    <input type="radio" id="radio-type_LP" name="type" value="LP" required aria-required="true"/>
                    Long play
                </label>
                <label for="radio-type_EP">
                    <input type="radio" id="radio-type_EP" name="type" value="EP" required aria-required="true"/>
                    Extended play
                </label>
            </fieldset>
        </li>
        <li>
            <fieldset>
                <legend>Inches:</legend>
                
                <label for="radio-inches_7">
                    <input type="radio" id="radio-inches_7" name="inch" value="7" required aria-required="true"/>
                    7 in
                </label>
                <label for="radio-inches_10">
                    <input type="radio" id="radio-inches_10" name="inch" value="10" required aria-required="true"/>
                    10 in
                </label>
                <label for="radio-inches_12">
                    <input type="radio" id="radio-inches_12" name="inch" value="12" required aria-required="true"/>
                    12 in
                </label>
            </fieldset>
        </li>
        <li>
            <fieldset>
                <legend>Rpm:</legend>
                <label for="radio-rpm_33">
                    <input type="radio" id="radio-rpm_33" name="rpm" value="33" required aria-required="true"/>
                    33 rpm
                </label>
                <label for="radio-45">
                    <input type="radio" id="radio-rpm_45" name="rpm" value="45" required aria-required="true"/>
                    45 rpm
                </label>
            </fieldset>
        </li>
        <li>
            <label for="input-add_cost">Price</label>
            <input type="number" name="price" min="0" step="0.01" id="input-add_cost" placeholder="€" required aria-required="true" />
        </li>
        <li>
            <label for="input-add_cost">Stock</label>
            <input type="number" name="stock" min="0" step="1" id="input-add_stock" required aria-required="true" />
        </li>
    </ul>
    <h3> Add a new Album </h3>
    <ul>
        <li>
            <label for="input-album_title">Title</label>
            <input type="text" id="input-album_title" name="album_title" required aria-required="true"/>
        </li>
        <li>
            <label for="input-album_artist">Artist</label>
            <input type="text" id="input-album_artist" name="album_artist" required aria-required="true" />
            <datalist id="datalist-album_artists">
                <?php foreach ($data['artists'] as $artist): ?>
                    <option id="option-artist_<?php echo $artist['id_artist']; ?>" value="<?php echo $artist['artist']; ?>">
                <?php endforeach; ?>
            </datalist>
        </li>
        <li>
            <label for="input-album_genre">Genre</label>
            <input type="text" id="input-album_genre" name="input-album_genre" required aria-required="true"/>
        <li>
            <label for="input-album_releasedate">Release Date</label>
            <input type="date" id="input-album_releasedate" name="album_releasedate" required aria-required="true" />
        </li>
        <li>
            <label for="input-album_cover">Cover</label>
            <input type="file" id="input-album_cover" name="album_cover" required aria-required="true" />
        </li>
    </ul>
    <div class="div"></div>
    <h3>Tracks</h3>
    <ul id="ul-tracks" class="add-tracks">
        <li class="split">
            <label for="input-track_title_1">Track #1</label>
            <input type="text" id="input-track_title_1" name="track_title" required aria-required="true" />
        </li>
        <li class="split">
            <label for="input-track_duration_1">Duration</label>
            <input type="text" id="input-track_duration_1" name="track_duration" required aria-required="true" />
        </li>
        <li>
            <div class="large button">
                <i class="bi bi-plus"></i>
                <input type="button" class="animate" id="btn-album_submit" value="Add"/>
            </div>
        </li>
    </ul>
</form>
<script src="/resources/js/album.js"></script>