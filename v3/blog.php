<?php
class BlogJsonLoader implements IBlogLoader{
    /**
     * @param String $path
     * @return array
     */
    public function load(String $path):array
    {
        $rawData = file_get_contents($path);
        return $this->parse($rawData);
    }
    /**
     * parse les données JSON et renvoie une liste d'articles
     * @param String $rawData donnees json_decodées
     * @return array
     */
    public function parse(String $rawData):array
    {
        $rawAuthors = json_decode($rawData, true)['authors'];
        $authors = array_map(function ($rawAuthor){
            return new Author(
                $rawAuthor['id'],
                $rawAuthor['firstname'],
                $rawAuthor['lastname']
            );
        }, $rawAuthors);
        $rawArticles = json_decode($rawData, true)['articles'];
        $articles = array_map(function ($rawArticle) use ($authors){
            $articleAuthorsId = $rawArticle['authorId'];
            $articleAuthors = array_filter($authors, function($author) use ($articleAuthorsId){
                return $author->id == $articleAuthorsId;
            });
            $articleAuthor = current($articleAuthors);
            return new Article(
                $rawArticle['id'],
                $rawArticle['title'],
                $rawArticle['content'],
                $articleAuthor,
                new DateTime($rawArticle['date'])
            );
        }, $rawArticles);
        return $articles;
    }
}
/**
 * Class BlogCSVLoader charge les articles depuis fichier csv
 * id / title / content / date / authorId / firstname / lastname
 */
class BlogCSVLoader extends BlogJsonLoader{
    function parseCSV(String $rawData){
        $csvParse = array_map('str_getcsv', file($rawData));
        $articles = array_map(function($article){
            $author = new Author(
                $article[0],
                $article[1],
                $article[2]
            );
            return new Article(
                $article[3],
                $article[4],
                $article[5],
                $author,
                new DateTime($article[7])
            );
        },$csvParse);
        return $articles;
    }
}
/**
 * Class BlogDBLoader charge les articles depuis une base de données
 */
class BlogDBLoader implements IBlogLoader{
    /**
     * @param $dbname
     */
    function load(String $path):array
    {
        //Connexion à la base de donnée
        try {
            $connexion = new PDO(
                'mysql:host=localhost; dbname=' .$path. '; charset-utf-8',
                'root', 'root');
        } catch (Exception $e) {
            die('Erreur: ' . $e->getMessage());
        }

        //On prépare les données de auteurs
        $requeteAuthors = "SELECT id, prenom, nom from auteurs";
        $resultatAuthors = $connexion->prepare($requeteAuthors);
        $resultatAuthors->execute();
        $dataAuthors = $resultatAuthors->fetchAll(PDO::FETCH_ASSOC);
        //On va créer un nouvel auteur pour chaque ligne du tableau d'auteurs de la bdd
        $authors = array_map(function($author){
            return new Author(
                $author['id'],
                $author['prenom'],
                $author['nom']
            );
        }, $dataAuthors);

        //On prépare les données de articles
        $requeteArticles = "SELECT * from articles";
        $resultatArticles = $connexion->prepare($requeteArticles);
        $resultatArticles->execute();
        $dataArticles = $resultatArticles->fetchAll(PDO::FETCH_ASSOC);
        //On va créer de nouveaux articles à partir du tableau venant de la bdd
        $articles = array_map(function($article) use ($authors){
            $articleAuthorsId = $article['id_authors'];
            //On filtre les auteurs afin de faire correspondre (id de l'auteur) avec (auteur_id de article)
            $articleAuthors = array_filter($authors, function($author) use ($articleAuthorsId){
                return $author->id == $articleAuthorsId;
            });
            $articleAuthor = current($articleAuthors);
            //Création d'un nouvel article avec les données qui ont été récupéré
            return new Article(
                $article['id_article'],
                $article['title'],
                $article['content'],
                $articleAuthor,
                new DateTime($article['date'])
            );
        },$dataArticles);
        return $articles;
    }
}
interface IBlogLoader{
    /**
     * @param String $path
     * @return array Article
     */
    function load(String $path):array;
}
/**
 * Class Author
 * description d'un rédacteur
 */
class Author{
    public $id;
    public $firstName;
    public $LastName;
    public function __construct(int $id, String $firstName, String $lastName)
    {
        $this->id = $id;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
    }
    /**
     * renvoie le nom complet : Bob Lee
     * @return String
     */
    function getName(): String
    {
        return $this->firstName. " " .$this->lastName;
    }
    /**
     * renvoie le initial du prénom et nom complet : B.Lee
     * @return String
     */
    function getShortName(): String
    {
        return $this->firstName[0]. "." .$this->lastName;
    }
    /**
     * renvoie les initiales : B.L
     * @return String
     */
    function getInitial(): String
    {
        return $this->firstName[0]. "." .$this->lastName[0];
    }
}
/**
 * Class Article
 */
class Article{
    public $id;
    public $title;
    public $content;
    public $author;
    public $publicationDate;
    public function __construct(Int $id, String $title, String $content, Author $author, DateTime $date)
    {
        $this->id = $id;
        $this->title = $title;
        $this->content = $content;
        $this->publicationDate = $date;
        $this->author = $author;
    }
}
class ArticleRenderer{
    public function __construct(Article $article)
    {
        $this->article = $article;
    }
    /**
     * renvoie l'article mis en forme
     * <h2>titre</h2>
     * <p>article</p>
     * <p>par nom-court, le date </p>
     * @return String
     */
    function render(): String
    {
        return "<h2>". $this->article->title ."</h2>"
            ."<p>". $this->article->content ."</p>"
            ."<p>". $this->article->author->getShortName() ."</p>"
            ." Ecrit le " .$this->article->publicationDate->format('d-m-Y')
            ."<p><a href=$_SERVER[PHP_SELF]>Accueil</a></p>";
    }
}
class Blog{
    public $title;
    public $articles;
    public function __construct(String $title, array $articles)
    {
        $this->title = $title;
        $this->articles = $articles;
    }
    /**
     * Renvoie le header  du blog
     * <header>titre
     * @return String
     */
    function displayHeader(): String
    {
        return "<h1>" .$this->title. "</h1>";
    }
    /**
     * affiche la liste des titres d'articles sous formes de liens vers les articles
     */
    function displayArticleList(): String
    {
        $articleLinks = array_map(function($article){
            return "<a href=\"". $_SERVER['PHP_SELF']. "?articleId="
                .$article->id."\">$article->title</a>";
        },$this->articles);
        return join("<hr/>", $articleLinks);
    }
    /**
     * renvoie le contenu HTML d'un article
     * @param int $articleId
     * @return String
     */
    function displayArticle(int $articleId): String
    {
        $selectedArticle = current(array_filter($this->articles,function($article) use ($articleId){
            return $article->id == $articleId ;
        }));
        $renderer = new ArticleRenderer($selectedArticle);
        return $renderer->render();
    }
    /**
     * renvoie un footer avec la date du jour
     * <footer></footer>
     */
    function displayFooter()
    {
        $date = New DateTime();
        return "<footer><p>Nous sommes le " .$date->format('d-m-Y').".</p>";
    }
}
// et pourquoi pas essayer de trouver 2/3 trucs à mettre dans un "helper"
class ViewHelper{
}
$loader = new BlogDBLoader();
$articles = $loader->load('blog');
$blog = new Blog('Vive la POO', $articles);
?>

<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= $blog->title ?></title>
</head>
<body>
<?= $blog->displayHeader(); ?>
<?= isset($_GET['articleId']) ? $blog->displayArticle($_GET['articleId']) : $blog->displayArticleList(); ?>
<?= $blog->displayFooter(); ?>
</body>
</html>
