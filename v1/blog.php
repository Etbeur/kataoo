<?php

class BlogLoader
{
    /**
     * @param String $path
     * @return array
     */
    function load(String $path): array
    {
        $dataFile = file_get_contents($path);
        return json_decode($dataFile, true);

    }



}

/**
 * Class Autor
 * description d'un rédacteur
 */
class Autor
{
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
        return "<p>" .$this->firstName. " " .$this->lastName. "</p>";
    }

    /**
     * renvoie le initial du prénom et nom complet : B.Lee
     * @return String
     */
    function getShortName(): String
    {
        $firstName_firstLetter = substr($this->firstName, 0, 1);
        return "<p>" .$firstName_firstLetter. "." .$this->lastName. "</p>";
    }

    /**
     * renvoie les initiales : B.L
     * @return String
     */
    function getInitial(): String
    {
        $firstName_firstLetter = substr($this->firstName, 0, 1);
        $lastName_firstLetter = substr($this->lastName, 0, 1);
        return "<p>" .$firstName_firstLetter. "." .$lastName_firstLetter. "</p>";
    }
}

/**
 * Class Article
 */
class Article
{
    public $id;
    public $title;
    public $content;
    public $autor;
    public $date;

    public function __construct(Int $id, String $title, String $content, Autor $autor, DateTime $date)
    {
        $this->id = $id;
        $this->title = $title;
        $this->content = $content;
        $this->autorId = $autor;
        $this->date = $date;
    }
}


class ArticleRenderer
{
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

        $VueFin = "";
        $VueFin .= "<h2>" .$this->article['title']. "</h2>";
        $VueFin .= "<p>" .$this->article['content']. "</p>";
        $VueFin .= "<p>" .$this->article['autorId']. "</p>";

    }
}

class Blog
{

    public function __construct(String $title, array $articles)
    {
        $this->title = $title;
        $this->autors = $articles['autors'];
        $this->articles = $articles['articles'];
    }

    /**
     * Renvoie le header  du blog
     * <header>titre
     * @return String
     */
    function displayHeader(): String
    {
        return "<header><h1>" .$this->title. "</h1></header> <br/>";
    }

    /**
     * affiche la liste des titres d'articles sous formes de liens vers les articles
     */
    function displayArticleList(): String
    {
        $finale = "";
        foreach ($this->articles as $article)
        {
            $finale .= "<a href='blog.php?articleId=" .$article['id']. "'>" .$article['title']. "</a><br/><br/>";
        }
        return $finale;
    }

    /**
     * renvoie le contenu HTML d'un article
     * @param int $articleId
     * @return String
     */
    function displayArticle(int $articleId): String
    {
        $articleFinal = '';
        $autorArticle = '';
        $articleDate = '';
       foreach ($this->articles as $article)
       {
           if($articleId == $article['id'])
           {
               $articleFinal =  $article['content'];

               foreach ($this->autors as $autor)
               {
                   if($article['autorId'] == $autor['id'])
                       $autorArticle = $autor['firstname'] ." ". $autor['lastname'];
               }

               foreach ($this->articles as $date)
               {
                   if($articleId == $date['id'])
                   {
                       $articleDate = new DateTime($article['date']);

                   }
               }
           }
       }
       return $articleFinal ."<p>Ecrit par " .$autorArticle. " le ".$articleDate->format('d-m-Y'). ".</p> <p><a href='blog.php'>Retour Accueil</a></p>";
    }

    /**
     * renvoie un footer avec la date du jour
     * <footer></footer>
     */
    function displayFooter()
    {
        $date = New DateTime();
        return "<p><a href='https://etbeur.github.io'>GithubPage</a></p>" ."<p>Nous somme le " .$date->format('d-m-y H:i:s'). "</p>";
    }
}

// et pourquoi pas essayer de trouver 2/3 trucs à mettre dans un "helper"
class ViewHelper
{

}

$loader = new BlogLoader();
$articles = $loader->load('blog.json');
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
