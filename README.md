# mais
An API to authenticate SIAM UB accocunt. This API only authenticate once and then returns the data, not session-based.

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
}
```