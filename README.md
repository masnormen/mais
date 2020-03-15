# mais
An API to authenticate SIAM UB and return user profile data.

### URL & Method:

POST /index.php

### Parameter:

- nim: string
- password: string

### Response:

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
