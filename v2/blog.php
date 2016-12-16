<?php

class BlogLoader
{
    /**
     * @param String $path
     * @return array
     */
    function load(String $path): array
    {
        $rawAuthors = json_decode(file_get_contents($path), true)['authors'];
        $authors = array_map(function ($rawAuthor){
            return new Author($rawAuthor['id'], $rawAuthor['firstname'], $rawAuthor['lastname']);
        }, $rawAuthors);

        $rawArticles = json_decode(file_get_contents($path), true)['articles'];
        $articles = array_map(function ($rawArticles) use ($authors){
            $articleAuthorsId = $rawArticles['authorId'];

            $articleAuthors = array_filter($authors, function($author) use ($articleAuthorsId){
                return $author->id == $articleAuthorsId;
            });

            $articleAuthor = current($articleAuthors);

            return new Article($rawArticles['id'], $rawArticles['title'], $rawArticles['content'], $articleAuthor, new DateTime($rawArticles['date']));

        }, $rawArticles);

        return $articles;
    }



}

/**
 * Class Author
 * description d'un rédacteur
 */
class Author
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
class Article
{
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


        return "<h2>". $this->article->title ."</h2>"
            ."<p>". $this->article->content ."</p>"
            ."<p>". $this->article->author->getShortName() ."</p>"
            ." Ecrit le " .$this->article->publicationDate->format('d-m-Y');

    }
}

class Blog
{
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
