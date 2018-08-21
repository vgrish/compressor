## Compressor

Принцип работы прост, стили и скрипты в специальной разметке собираются в один файл и могут быть подключены, либо помещены прямо в тело страницы.

### Пример

```
<!-- footer-css -->
<link rel="stylesheet" href="/assets/components/minishop2/css/web/lib/fotorama.min.css" type="text/css" />
<link rel="stylesheet" href="/assets/components/minishop2/css/web/lib/jquery.jgrowl.min.css" type="text/css" />
<!-- footer-css -->
```

```
<!-- footer-scripts -->
<script src="/assets/components/themebootstrap/js/jquery.min.js"></script>
<script src="/assets/components/themebootstrap/js/bootstrap.min.js"></script>
<script src="/assets/components/minishop2/js/web/lib/fotorama.min.js"></script>
<script src="/assets/components/minishop2/js/web/lib/jquery.jgrowl.min.js"></script>

<script type="text/javascript">
    var xxx = [];
</script>
<!-- footer-scripts -->

```


### Исключаемые теги
Для исключения обработки контента пакетом следует использовать следующие теги

```
<!--noindex-->
noindex content
<!--/noindex-->
           
<!--nocompress-->
nocompress content
<!--/nocompress-->
```

### Сжать нужные файлы в один

```
{'compress'|snippet:[
'cssFile' => [
  '1.css',
  '2.css'
],
'jsFile' => [
  '1.js',
  '2.js'
]
]}
```

