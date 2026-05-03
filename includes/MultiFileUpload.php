<?php
// includes/MultiFileUpload.php
//
// PHP gives multi-file uploads as parallel arrays inside $_FILES['name'][i],
// $_FILES['type'][i], etc. Every upload form was reshaping that into per-file
// arrays with the same boilerplate loop. This consolidates the pattern.

class MultiFileUpload {

    /**
     * Reshape a $_FILES['key'] entry into per-file arrays keyed by their
     * original index in the upload, so callers can correlate parallel
     * arrays (e.g. POST'd labels). Skips empty slots and slots whose upload
     * errored.
     *
     * @return array<int, array{name:string,type:string,tmp_name:string,error:int,size:int}>
     */
    public static function parse(?array $filesArray): array {
        if (empty($filesArray) || empty($filesArray['name'])) {
            return [];
        }

        $out   = [];
        $count = count($filesArray['name']);
        for ($i = 0; $i < $count; $i++) {
            if (empty($filesArray['name'][$i])) {
                continue;
            }
            if (($filesArray['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }
            $out[$i] = [
                'name'     => $filesArray['name'][$i],
                'type'     => $filesArray['type'][$i],
                'tmp_name' => $filesArray['tmp_name'][$i],
                'error'    => $filesArray['error'][$i],
                'size'     => $filesArray['size'][$i],
            ];
        }
        return $out;
    }
}
