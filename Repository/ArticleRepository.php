<?php

namespace ServerGrove\KbBundle\Repository;

use Doctrine\ODM\PHPCR\DocumentRepository;
use Doctrine\ODM\PHPCR\Id\RepositoryIdInterface;
use ServerGrove\KbBundle\Document\Article;
use ServerGrove\KbBundle\Document\Category;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * ArticleRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ArticleRepository extends DocumentRepository implements RepositoryIdInterface
{

    public function findOneBySlug($slug)
    {
        return $this->find('/articles/'.$slug);
    }

    /**
     * @param $search
     *
     * @return \Doctrine\Common\Collections\Collection
     *
     * @TODO Implement this method with a query builder
     */
    public function search($search, Category $category = null)
    {
        /** @var $articles ArrayCollection */
        if (is_null($category)) {
            $articles = $this->findAll();
        } else {
            $articles = $this->getArticlesFromCategoryHierarchy($category, new ArrayCollection());
        }

        $filtered = $articles->filter(
            function (Article $article) use ($search) {
                return 0 == strcasecmp($article->getTitle(), $search)
                    || in_array($search, $article->getKeywords()->getValues())
                    || false !== stripos($article->getTitle(), $search)
                    || false !== stripos($article->getContent(), $search);
            }
        );

        return $filtered;
    }

    /**
     * @param  Category        $category
     * @param  ArrayCollection $articles
     * @return ArrayCollection
     */
    public function getArticlesFromCategoryHierarchy(Category $category, ArrayCollection $articles)
    {
        if (is_null($articles)) {
            $articles = new ArrayCollection();
        }

        foreach ($category->getArticles() as $article) {
            $articles->add($article);
        }

        foreach ($category->getChildren() as $child) {
            $this->getArticlesFromCategoryHierarchy($child, $articles);
        }

        return $articles;
    }

    /**
     * Generate a document id
     *
     * @param Article $document
     * @param object  $parent
     *
     * @return string
     */
    public function generateId($document, $parent = null)
    {
        /** @var $session \PHPCR\SessionInterface */
        $session = $this->getDocumentManager()->getPhpcrSession();
        $root    = $session->getNode('/');

        if (!$root->hasNode('articles')) {
            $root->addNode('articles');
        }

        return '/articles/'.$document->getSlug();
    }
}
