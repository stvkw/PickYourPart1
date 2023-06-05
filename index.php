<?php

//################ PHP/MYSQLfor website ############
//# Description: setting up a structure for a PHP/MySQL-driven site
//# template dbtable would hold front-end with supporting dbtables for content and style
//# issues: 
//# .htaccess is not working on VSCode yet.  using querystring for now to hold topic name
//# coding and db are in progress

    // html page use when db is down
    include './assets/include/siteDownHomepage.php';

    function getTopicId($db) {
    // gets the topic name from uri or querystring. name must exist or use default topic

        $topicName = (empty($_GET['topic'])) ? " " : $_GET['topic'];

        // prepping sql query
        $sqlquery = "SELECT Id FROM topic WHERE (name LIKE :topName AND is_visible=1) OR Id=1 ORDER BY Id DESC LIMIT 1";
        // use default Id=1 if no Id found matching given name
        $stmt = $db->prepare($sqlquery);
        $stmt->bindValue(':topName', $topicName, PDO::PARAM_STR);

        if ($stmt->execute()) { 
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $topicId = $result['Id']; 
        } else {
            // query failed so flag it
            $topicId = 0;
        }

        $result->free();
        return ($topicId);
    }  // end getTopicId()


    function getTopicPageTemplate($db, $topicId) {
        // get query q value for later use
        // DB query for the HTMLtemplate and its contents
        // 

        $qwords = (empty($_GET['q'])) ? " " : $_GET['q'];
        // $qword not implement

        // prepping sql query 
        $sqlquery = "SELECT a.name, a.shortDescription, b.htmlTemplate
            FROM topic AS a JOIN template AS b ON a.templateId=b.Id
            WHERE a.Id = :topicId";
        $stmt = $db->prepare($sqlquery);
        $stmt->bindValue(':topicId', $topicId, PDO::PARAM_STR);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $name = $result['name'];
        $shortDescription = $result['shortDescription'];
        $HTMLtemplate = $result['htmlTemplate'];

        // replace vars with data in HTMLtemplate
        $patterns = array('/::name::/', '/::shortDescription::/'); 
        $replacements = array($name, $shortDescription);
        $HTMLtemplate = preg_replace($patterns, $replacements, $HTMLtemplate);  

        // clean and return page
        $result->free();
        return ($HTMLtemplate);
    }  // end getMainPageTemplate()



// Main

      // if db is down, show default homepage
      $page = $siteDownHomepage;
      $topicId = null;
      $page = null;

      try {
        // test db read-only access
        $db = new PDO('mysql:host=somedbserver.com;dbname=someDB;charset=utf8mb4', 'root', '111');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        $topicId = getTopicId($db);

        if ($topicId >= 1) { 
            $page = getTopicPageTemplate($db, $topicId);
            // if $topicId < 1 then $page already = $siteDownHomepage;
        }

      } catch(PDOException $ex) {
        // muted error mesg 
        // echo "An Error occured!<br />".$ex->getMessage();
      } catch (Exception $ex) {
        // muted error mesg 
        // echo "General Error: The user could not be added.<br />".$ex->getMessage();
      }  // end try catch

      echo $page;   // send off page

// End Main
?>