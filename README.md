# mais
An API to authenticate SIAM UB account and return user data.

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

## TODO:
- Use BAIS UB instead of SIAM for more consistent data fetching.
