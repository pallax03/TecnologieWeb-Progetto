<?php
final class VinylsModel {

    private $db = null;

    private $notifications_model = null;

    public function __construct() {
        $this->db = Database::getInstance();
        require_once MODELS . 'NotificationModel.php';
        $this->notifications_model = new NotificationModel();
    }

    private function broadcastCartVinyl($id_vinyl) {
        $this->notifications_model->broadcastFor(
            Database::getInstance()->executeResults(
                "SELECT id_user FROM carts WHERE id_vinyl = ?",
                'i',
                $id_vinyl
            ),
            "A vinyl in your cart has been updated.",
            "/vinyl?id=" . $id_vinyl
        );
    }

    private function broadcastVinyl($id_vinyl) {
        $this->notifications_model->broadcast(
            "A new Vinyl landed here!",
            "/vinyl?id=$id_vinyl"
        );
    }

    private function applyFilters($query, $filters = []) {
        $filtersMap = [
            "id_vinyl" => ["query" => fn($value) => " AND v.id_vinyl = ? ", "type" => 'i'],
            "id_album" => ["query" => fn($value) => " AND a.id_album = ? ", "type" => 'i'],
            "title" => ["query" => fn($value) => " AND a.title LIKE ?", "type" => 's', "value" => fn($value) => "%$value%"],
            "genre" => ["query" => fn($value) => " AND a.genre LIKE ?", "type" => 's', "value" => fn($value) => "%$value%"],
            "id_artist" => ["query" => fn($value) => " AND ar.id_artist = ? ", "type" => 'i'],
            "artist_name" => ["query" => fn($value) => " AND ar.name LIKE ?", "type" => 's', "value" => fn($value) => "%$value%"],
            "id_track" => ["query" => fn($value) => " AND ta.id_track = ? ", "type" => 'i'],
            "track_title" => ["query" => fn($value) => " AND t.title LIKE ?", "type" => 's', "value" => fn($value) => "%$value%"]
        ];

        $types = '';
        $values = [1];
        foreach ($filters as $key => $value) {
            $query .= $filtersMap[$key]["query"]($value);
            $types .= $filtersMap[$key]["type"];
            $values[] = isset($filtersMap[$key]["value"]) ? $filtersMap[$key]["value"]($value) : $value;
        }
        return $this->db->executeResults($query. ' GROUP BY a.id_album;', 'i'.$types, ...$values);
    }

    /**
     * Gets a specified number of vinyls from the database.
     * @param n the number of vinyls to send
     * @param params a map of values to query the database on:
     * params structure:
     *  { 
     *      id -> ..., album -> ..., genre -> ...,
     *      artist -> ..., track -> ...
     *  }
     * @return json of vinyls data
     */    
    public function getVinyls($n, $params) {
        $query = "SELECT
            v.id_vinyl,
            v.cost,
            a.title,
            a.cover,
            a.genre,
            ar.name AS artist
            FROM
            vinyls v JOIN albums a ON v.id_album = a.id_album
            JOIN artists ar ON a.id_artist = ar.id_artist";
        $keys = array_keys($params);
        // get the first key and switch on it to get the right string added to the query
        switch (reset($keys)) {
            case "id":
                $query = $query . " WHERE v.id_vinyl = " . $params["id"];
                break;
            case "album":
                $query = $query . " WHERE a.title LIKE '%" . $params["album"] . "%'";
                break;
            case "genre":
                $query = $query . " WHERE a.genre LIKE '%" . $params["genre"] . "%'";
                break;
            case "track":
                $query = $query . " JOIN albumstracks ta
                    ON a.id_album = ta.id_album JOIN tracks t
                    ON t.id_track = ta.id_track WHERE t.title LIKE '%". $params["track"] . "%'";
                    break;
            case "artist":
                $query = $query . " WHERE ar.name LIKE '%" . $params["artist"] . "%'";
                break;
            default;
        }
        // in case it needs a limitation
        if ($n > 0) {
            $query = $query . " LIMIT ?";
            $result = $this->db->executeResults($query, 'i', $n);
        } else {
            $result = $this->db->executeResults($query);
        }
        return $result;
    }

    public function getAllVinyls() {
        $vinyls = "SELECT
            v.id_vinyl,
            a.title,
            v.type,
            v.inch,
            v.rpm,
            v.cost
            FROM vinyls v , albums a
            WHERE v.id_album=a.id_album";
        return $this->db->executeResults($vinyls);
    }

    /**
     * Gets the full of a single vinyl (vinyl page)
     * from a given id.
     * @param id of the vinyl in question
     * @return array containing details on the vinyl
     */
    public function getVinylDetails($id) {
        // query to get vinyls info
        $vinyl = "SELECT 
            v.id_vinyl,
            v.cost,
            v.rpm,
            v.inch,
            v.type,
            a.id_album,
            a.title,
            a.release_date,
            a.cover,
            a.genre,
            ar.name AS artist
            FROM 
            vinyls v
            JOIN albums a ON v.id_vinyl = a.id_album
            JOIN artists ar ON ar.id_artist = a.id_artist
            WHERE v.id_vinyl = ?";
        // query to get the tracks from a vinyl [needs id_album from previous query]
        $tracks = "SELECT
            t.title,
            t.duration
            FROM
            albums a
            JOIN albumstracks ta ON ta.id_album = a.id_album
            JOIN tracks t ON t.id_track = ta.id_track
            WHERE a.id_album = ?";
        // prepare statement
        $result["details"] = $this->db->executeResults($vinyl, "i", $id)[0];
        // store id_album for the next query
        $album =  $result["details"]["id_album"];
        // prepare second statement
        $result["tracks"] = $this->db->executeResults($tracks, "i", $album);
        return $result;
    }

    /**
     * get the vinyl details from the database (without tracks)
     * 
     * @param int $id_vinyl the id of the vinyl to get the details of
     * 
     * @return array containing the details of the vinyl
     */
    public function getVinyl($id_vinyl) {
        return Database::getInstance()->executeResults(
            "SELECT 
                v.id_vinyl,
                v.stock,
                v.cost,
                v.rpm,
                v.inch,
                v.type,
                a.title,
                a.genre,
                a.cover,
                ar.name AS artist_name
                FROM vinyls v 
                JOIN albums a ON v.id_vinyl = a.id_album
                JOIN artists ar ON a.id_artist = ar.id_artist
                WHERE id_vinyl = ?",
            'i',
            $id_vinyl
        )[0];
    }


    /**
     * Function to be called to get the preview of a vinyl
     * (cart, checkout and order pages).
     * @param int $id of the vinyl to get the preview of
     * @return array containing the information on the vinyl
     */
    public function getPreview($id) {
        $preview = [];
        // query to get vinyls info
        $query = "SELECT
            v.cost,
            v.rpm,
            v.inch,
            v.type,
            a.title,
            a.genre,
            a.cover,
            ar.name AS artist
            FROM 
            vinyls v
            JOIN albums a ON v.id_vinyl = a.id_album
            JOIN artists ar ON ar.id_artist = a.id_artist
            WHERE v.id_vinyl = ?";
        // execute query
        $result = $this->db->executeResults($query);
        if (!empty($result)):
            // store results
            $preview["cost"] = $result["cost"];
            $preview["rpm"] = $result["rpm"];
            $preview["inch"] = $result["inch"];
            $preview["genre"] = $result["genre"];
            $preview["type"] = $result["type"];
            $preview["title"] = $result["title"];
            $preview["cover"] = $result["cover"];
            $preview["artist"] = $result["artist"];
        endif;
        return $preview;
    }

    /**
     * Gets the order previews (user page)
     * from a given vinyl id.
     * @param id of the vinyl to get the preview of
     * @return json with the  preview infos
     */
    public function getUserOrderPreview($id) {
        $preview = [];
        $query = "SELECT
            v.cost,
            a.title,
            a.cover,
            FROM 
            vinyls v
            JOIN albums a ON v.id_vinyl = a.id_album
            WHERE v.id_vinyl = ?";
        // execute query
        $result = $this->db->executeResults($query, 'i', $id);
        if (!empty($result)):
            // store results
            $preview["cost"] = $result["cost"];
            $preview["title"] = $result["title"];
            $preview["cover"] = $result["cover"];
        endif;
        return $preview;
    }

    public function getSuggested($id) {
        $infos = "SELECT
            a.genre,
            ar.name AS artist
            FROM
            vinyls v
            JOIN albums a ON v.id_album = a.id_album
            JOIN artists ar ON ar.id_artist = a.id_artist
            WHERE v.id_vinyl = ?";
        $vinyls = "SELECT
            v.id_vinyl,
            a.cover,
            a.title
            FROM
            vinyls v
            JOIN albums a ON v.id_album = a.id_album
            JOIN artists ar ON ar.id_artist = a.id_artist
            WHERE (a.genre = ? OR ar.name = ?)
            AND v.id_vinyl <> ?
            LIMIT 6";
        // execute query
        $info = $this->db->executeResults($infos, "i", $id)[0];
        // store infos
        $result = $this->db->executeResults($vinyls, "ssi", $info["genre"], $info["artist"], $id);
        return $result;
    }

    
    /**
     * Get the vinyls.
     *
     * @param array $filter the qury filter of the artist params to get the albums
     * filters can be:
     * - id_album
     * - title
     * - genre
     * - id_artist
     * - artist_name
     * - id_track
     * - track_title
     * 
     * @return array containing the albums 
     */
    public function getVinylsOptimized($filters) {
        return $this->applyFilters(
            "SELECT v.id_vinyl,
                    v.cost,
                    a.id_album,
                    a.title,
                    a.release_date,
                    a.genre,
                    a.cover,
                    ar.id_artist,
                    ar.name AS artist_name,
                    GROUP_CONCAT(DISTINCT ta.id_track ORDER BY ta.id_track ASC SEPARATOR ', ') AS track_ids,
                    GROUP_CONCAT(DISTINCT t.title ORDER BY t.title ASC SEPARATOR ', ') AS track_titles
                FROM albums a 
                JOIN artists ar ON a.id_artist = ar.id_artist
                JOIN albumstracks ta ON a.id_album = ta.id_album
                JOIN tracks t ON t.id_track = ta.id_track
                WHERE 1 = ?", 
            $filters
        );
    }

    /**
     * Get the albums of an artist.
     * @param array $filter the qury filter of the artist params to get the albums
     * filters can be:
     * - id_album
     * - title
     * - genre
     * - id_artist
     * - artist_name
     * - id_track
     * - track_title
     * 
     * @return array containing the albums 
     */
    public function getAlbums($filters) {
        return $this->applyFilters(
            "SELECT 
                    a.id_album,
                    a.title,
                    a.release_date,
                    a.genre,
                    a.cover,
                    ar.id_artist,
                    ar.name AS artist_name,
                    GROUP_CONCAT(DISTINCT ta.id_track ORDER BY ta.id_track ASC SEPARATOR ', ') AS track_ids,
                    GROUP_CONCAT(DISTINCT t.title ORDER BY t.title ASC SEPARATOR ', ') AS track_titles
                FROM albums a 
                JOIN artists ar ON a.id_artist = ar.id_artist
                JOIN albumstracks ta ON a.id_album = ta.id_album
                JOIN tracks t ON t.id_track = ta.id_track
                WHERE 1 = ?", 
            $filters
        );
    }

    // TODO NEED TO UPDATE ALBUMS TRACKS


    /**
     * Check if the artist exists in the database.
     * @param int $artist_id of the artist to check
     * 
     * @return bool true if the artist exists, false otherwise
     */
    private function checkArtist($artist_id) {
        return !empty($this->db->executeResults(
            "SELECT * FROM artists WHERE id_artist = ?",
            'i',
            $artist_id
        ));
    }


    /**
     * Create an artist in the database.
     * @param string $name of the artist
     * 
     * @return bool true if the artist was created, false otherwise
     */ 
    public function createArtist($name) {
        return $this->db->executeQueryAffectRows(
            "INSERT INTO artists (name) VALUES (?)",
            's',
            $name
        );
    }


    /**
     * Check if the album exists in the database.
     * @param int $album_id of the album to check
     * 
     * @return bool true if the album exists, false otherwise
     */
    private function checkAlbum($album_id) {
        return !empty($this->db->executeResults(
            "SELECT * FROM albums WHERE id_album = ?",
            'i',
            $album_id
        ));
    }


    /**
     * Create an album in the database.
     * @param string $title of the album
     * @param string $release_date of the album
     * @param string $genre of the album
     * @param string $cover of the album
     * @param array $artist of the album
     * 
     * @return bool true if the album was created, false otherwise
     */
    public function createAlbum($title, $release_date, $genre, $cover, $artist) {
        // check if the artist already exists if not add it to the database
        if(is_array($artist) && !$this->checkArtist($artist["id_artist"])) {
            $artist = $this->createArtist($artist["name"]);
            if(!$artist) {
                return false;
            }
        } else {
            return false;
        }

        $artist = $artist["id_artist"];

        return $this->db->executeQueryAffectRows(
            "INSERT INTO albums (title, release_date, genre, cover, id_artist)
                VALUES (?, ?, ?, ?, ?)",
            'ssssi',
            $title, $release_date, $genre, $cover, $artist
        );
    }

    /**
     * Add or update a vinyl to the database.
     * 
     * @param int $id_vinyl of the vinyl to update (null if adding)
     * @param array $album of the vinyl
     * @param array $artist of the vinyl's album
     * @param float $cost of the vinyl
     * @param int $stock of the vinyl
     * 
     * @return bool true if the vinyl was added, false otherwise
     */
    public function addVinyl($cost, $rpm, $inch, $type, $stock, $album, $artist, $id_vinyl = null) {
        // check if the album already exists if not add it to the database
        if(is_array($album) && !$this->checkAlbum($album["id_album"])) {
            $album = $this->createAlbum($album["title"], $album["release_date"], $album["genre"], $album["cover"], $artist);
            if(!$album) {
                return false;
            }
        }
        $album = $album["id_album"];

        if ($id_vinyl) {
            return $this->updateVinyl($id_vinyl, $cost, $rpm, $inch, $type, $stock, $album);
        }
        
        $result = $this->db->executeQueryAffectRows(
            "INSERT INTO vinyls (`cost`, `rpm`, `inch`, `type`, `stock`, `id_album`)
                VALUES (?, ?, ?, ?, ?, ?)",
            'diisii',
            $cost, $rpm, $inch, $type, $stock, $album
        );

        if ($result) {
            // broadcast the new vinyl for all users
            $last_id = Database::getInstance()->executeResults("SELECT id_vinyl FROM vinyls ORDER BY id_vinyl DESC LIMIT 1")[0]['id_vinyl'];
            $this->broadcastVinyl($last_id);
        }
        return $result;
    }

    /**
     * Delete a vinyl from the database.
     * @param int $id_vinyl of the vinyl to delete
     * 
     * @return bool true if the vinyl was deleted, false otherwise
     */
    public function deleteVinyl($id_vinyl) {
        return $this->db->executeQueryAffectRows(
            "DELETE FROM vinyls WHERE id_vinyl = ?",
            'i',
            $id_vinyl
        );
    }

    /**
     * Update a vinyl in the database.
     * @param int $id_vinyl of the vinyl to update
     * @param float $cost of the vinyl
     * @param int $rpm of the vinyl
     * @param int $inch of the vinyl
     * @param string $type of the vinyl
     * @param int $stock of the vinyl
     * 
     * @return bool true if the vinyl was updated, false otherwise
     */
    public function updateVinyl($id_vinyl, $cost, $rpm, $inch, $type, $stock, $id_album) {
        $result = $this->db->executeQueryAffectRows(
            "UPDATE vinyls
                SET cost = ?, rpm = ?, inch = ?, type = ?, stock = ?, id_album = ?
                WHERE id_vinyl = ?",
            'diisii',
            $cost, $rpm, $inch, $type, $stock, $id_album, $id_vinyl
        );

        if ($result) {
            $this->broadcastCartVinyl($id_vinyl);
        }
        return $result;
    }
}