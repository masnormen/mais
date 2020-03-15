# mais
An API to authenticate SIAM UB accocunt. Only returns user profile data once, not session-based.

## URL:
`/index.php`

## Method:
`POST`

## Parameter:
- `nim: string`
- `password: string`

## Response:
```
{
    status
    data {
        nama
        nim
        strata
        fakultas
        jurusan
        prodi
        seleksi
        no_ujian
        image
}
```
