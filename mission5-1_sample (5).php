<!DOCTYPE html>
 <html lang="ja">
 <head>
   <meta charset = "UTF-8">
   <title>Mission 5-1</title>
 </head>
 <body>
   <?php

   // tableの作成(存在しない場合)
   createTable();

   // 編集用グローバル変数などの初期化
   $get_id = "";
   $get_name = "";
   $get_comment = "";
   $get_pass = "";
   $delete_id = "";
   $edit_id = "";
   $datetime = date("Y-m-d H:i:s");
   $error_message = array(); // エラーメッセージを格納する配列

   ############## main処理 ##############
   // 入力フォームに新規投稿として入力されて送信された場合の処理
   if ( ( !empty( $_POST["name"] ) || !empty( $_POST["comment"] ) || !empty( $_POST["pass"] )) && empty( $_POST["get_num"] ) ) {
     $name = $_POST["name"];
     $comment = $_POST["comment"];
     $new_pass = $_POST["pass"]; 
     newPost( $name, $comment, $datetime, $new_pass );
     showPost();

   // 編集として入力フォームに入力されて送信された場合の処理
   } elseif ( ( !empty( $_POST["get_num"] ) ) ) {
     $name = $_POST["name"];
     $comment = $_POST["comment"];
     $edit_num = $_POST["get_num"];
     $edit_pass = $_POST["pass"];
     editPost( $edit_num, $name, $comment, $datetime, $edit_pass );
     showPost();
   
   // 削除フォームに入力されて送信された場合の処理 
   } elseif ( !empty( $_POST["delete_num"] ) || !empty( $_POST["delete_pass"] )) {
     $delete_num = $_POST["delete_num"];
     $delete_pass = $_POST["delete_pass"];
     deletePost( $delete_num, $delete_pass );
     showPost();
     
    // 編集フォームに入力されて送信された場合の処理  
   } elseif ( !empty( $_POST["edit_num"] ) || !empty( $_POST["edit_pass"] ) ) {
     $edit_num = $_POST["edit_num"];
     $edit_pass = $_POST["edit_pass"];
     getPost( $edit_num, $edit_pass );
     showPost();
   } else {
     showPost();
     echo "いずれかのフォームに入力してください<br>";
   }

   // エラーメッセージの表示
   if ( !empty( $error_message ) ) {
     foreach ( $error_message as $message ) {
       echo $message."<br>";
     }
     echo "<br>";
   }
   ############## main処理ここまで ##############

 

   // tableを作成(存在しない場合)する関数
   function createTable() {
     $pdo = dbConnect();
     $sql = "CREATE TABLE IF NOT EXISTS tb_board_5"
     ."("
     ."id INT AUTO_INCREMENT PRIMARY KEY," //id ・自動で登録されていくナンバリング
     ."name char(32)," // name ・名前を入れる．文字列，半角英数で32文字
     ."comment TEXT," // comment ・コメントを入れる．文字列，長めの文章も入る
     ."dt datetime,"  // dt・日付と時間を入れる．datetime型
     ."pass char(32)" // ps・パスワード．半角英数で32文字以内
     .");";
     $stmt = $pdo -> query($sql);
   }

   // 新規投稿を処理する関数
   function newPost( $name, $comment, $datetime, $pass ) {
     $pdo = dbConnect();
     global $get_name;
     global $get_comment;
     global $error_message;
     
     // 名前が入力されていない場合
     if ( empty( $_POST["name"] )) {
       $error_message[] = "名前を入力してください";
       $get_comment = $_POST["comment"];//無くても動いた。
     }
     
     // コメントが入力されていない場合
     if ( empty( $_POST["comment"] )) {
       $error_message[] = "コメントを入力してください";
       $get_name = $_POST["name"];
     } 

     // パスワードが入力されていない場合
     if ( empty( $_POST["pass"] )) {
       $error_message[] = "パスワードを入力してください";
       $get_name = $_POST["name"];
       $get_comment = $_POST["comment"];
     }

     // 名前もコメントもパスワードも入力されている場合
     if ( empty( $error_message ) ) {
       // dbのtableにデータを登録
       $sql = $pdo -> prepare("INSERT INTO tb_board_5(name, comment, dt, pass) VALUES (:name, :comment, :dt, :pass)"); // tb_board_5(name, comment, dt, password)に:name, :comment, :dt, :passwordというパラーメータを付与
       $sql -> bindParam(":name", $name, PDO::PARAM_STR);  // :nameという名前のパラメータに$nameを入れる．bindParamの第1引数はパラメータの指定，第2引数はパラメータを入れる変数の指定，第3引数は型の指定．PDO::PARAM_STRは，文字列の指定
       $sql -> bindParam(":comment", $comment, PDO::PARAM_STR);
       $sql -> bindParam(":dt", $datetime, PDO::PARAM_STR);
       $sql -> bindParam(":pass", $pass, PDO::PARAM_STR);
       $sql -> execute();

       echo "新規投稿を受け付けました<br><br>";
     }
   }

   // 投稿の削除を処理する関数
   function deletePost( $id, $pass ) {
     global $error_message;
     global $delete_id;

     // 編集対象番号が入力されていない場合
     if ( empty( $_POST["delete_num"] ) ) {
       $error_message[] = "編集対象番号を入力してください";
     }

     // パスワードが入力されていない場合
     if ( empty( $_POST["delete_pass"] ) ) {
       $error_message[] = "パスワードを入力してください";
       $delete_id = $_POST["delete_num"];
     }

     // 編集対象番号もパスワードも入力されている場合
     if ( empty( $error_message ) ) {
       $pdo = dbConnect();
       $sql = 'SELECT * FROM tb_board_5';
       $stmt = $pdo -> query($sql);
       // 入力されたものが1以上の整数かどうかを判定
       if ( !ctype_digit( $id ) || (int)$id < 1 ) {   // ctype_digit: 文字列数値を判定する関数
         echo "削除番号には1以上の整数を指定してください<br><br>";
           
       } else {
         // 指定された投稿番号のパスワードを取り出す
         $stmt = $pdo -> prepare('SELECT pass FROM tb_board_5 WHERE id=:id');
         $stmt -> bindParam(":id", $id, PDO::PARAM_INT);
         $stmt -> execute();
         $results = $stmt -> fetchAll();

         // 指定された番号の投稿(パスワード)があるかの確認
         if ( !empty( $results[0][0] ) ) {
           $saved_pass = $results[0][0];
           // パスワードが一致する場合
           if ( $pass == $saved_pass ) {
             $sql = 'DELETE FROM tb_board_5 WHERE id=:id';
             $stmt = $pdo -> prepare($sql);
             $stmt -> bindParam(":id", $id, PDO::PARAM_INT);
             $stmt -> execute();

             echo "投稿の削除を受け付けました<br><br>";

           // パスワードが正しくない場合
           } else {
             echo "パスワードが正しくありません<br><br>";
             $delete_id = $_POST["delete_num"];
           }

         // 指定された番号の投稿がない場合
         } else {
           echo "指定された番号の投稿はありません<br><br>";
         }
       }
     }
   }

   // 編集番号に対応する投稿を入力フォームに表示させる関数
   function getPost( $id, $pass ) {
     $pdo = dbConnect();
     global $error_message;
     global $edit_id;
     global $get_id;
    global $get_name;
    global $get_comment;
     global $get_pass;

     // 編集対象番号が入力されていない場合
     if ( empty( $_POST["edit_num"] ) ) {
       $error_message[] = "編集対象番号を入力してください";
     }
     
     // 入力されたものが1以上の整数でない場合
     if ( !ctype_digit( $id ) || (int)$id < 1 ) {   // ctype_digit: 文字列数値を判定する関数
       $error_message[] = "削除番号には1以上の整数を指定してください";
     } 

     // パスワードが入力されていない場合
     if ( empty( $_POST["edit_pass"] ) ) {
       $error_message[] = "パスワードを入力してください";
       $edit_id = $_POST["edit_num"];
     }

     // 編集対象番号もパスワードも入力されている場合
     if ( empty( $error_message ) ) {
       // 指定された投稿番号のパスワードを取り出す
       $stmt = $pdo -> prepare('SELECT * FROM tb_board_5 WHERE id=:id');
       $stmt -> bindParam(":id", $id, PDO::PARAM_INT);
       $stmt -> execute();
       $results = $stmt -> fetchAll();

       // 指定された番号があるかどうかの判定
       if ( !empty( $results[0][4] ) ) {
         // 指定された番号が存在する場合
         $saved_pass = $results[0][4];
          // パスワードが一致する場合
         if ( $pass == $saved_pass ) {
           $get_id = $results[0][0];
           $get_name = $results[0][1];
           $get_comment = $results[0][2];
           $get_pass = $results[0][4];

         // パスワードが正しくない場合
         } else {
           echo "パスワードが正しくありません<br><br>";
         }

       // 指定された番号が存在しない場合
       } else {
         echo "指定された番号の投稿はありません<br><br>";
       }
     }
   }

   // 投稿の編集を処理する関数
   function editPost( $id, $name, $comment, $datetime, $pass ) {
     global $get_name;
    global $get_comment;
     global $get_id;
     global $error_message;

     // 名前が入力されていない場合
     if ( empty( $_POST["name"] ) ) {
       $error_message[] = "名前を入力してください";
       $get_comment = $_POST["comment"];
       $get_id = $_POST["get_num"];
     }
     
     // コメントが入力されていない場合
     if ( empty( $_POST["comment"] ) ) {
       $error_message[] = "コメントを入力してください";
       $get_name = $_POST["name"];
       $get_id = $_POST["get_num"];
     } 

     // パスワードが入力されていない場合
     if ( empty( $_POST["pass"]) ) {
       $error_message[] = "パスワードを入力してください";
       $get_name = $_POST["name"];
       $get_comment = $_POST["comment"];
       $get_id = $_POST["get_num"];
     }
     
     // 名前もコメントもパスワードも入力されている場合
     if ( empty( $error_message ) ) {
       $pdo = dbConnect();
       $sql = 'UPDATE tb_board_5 SET name=:name,comment=:comment, dt=:dt, pass=:pass WHERE id=:id';
       $stmt = $pdo -> prepare($sql);
       $stmt -> bindParam(':name', $name, PDO::PARAM_STR);
       $stmt -> bindParam(':comment', $comment, PDO::PARAM_STR);
       $stmt -> bindParam(':dt', $datetime, PDO::PARAM_STR);
       $stmt -> bindParam(':pass', $pass, PDO::PARAM_STR);
       $stmt -> bindParam(':id', $id, PDO::PARAM_INT);
       $stmt -> execute();

       echo "編集を受け付けました<br><br>";
     }
   }

   // 投稿の内容を出力する関数
   function showPost() {
     $pdo = dbConnect();
     // dbのtableの内容を表示
     echo "【投稿一覧】<br>";
     $sql = 'SELECT * FROM tb_board_5';
     $stmt = $pdo -> query($sql); 
     $results = $stmt -> fetchAll();
     foreach ( $results as $row ) {
       echo $row["id"]." ".$row["name"]." ".$row["comment"]." ".$row["dt"]."<br>";
     }
     echo "<br>";
   }
   ?>

 <form action="" method="post">
     <h4>【　入力フォーム　】</h4>
     <p><input type="text" name="name" value="<?php echo $get_name ?>" placeholder = "name"></p>
     <p><input type="text" name="comment" placeholder = "comment" value="<?php echo $get_comment ?>"></input></p>
     <p><input type="password" name="pass" placeholder = "password" value="<?php echo $get_pass ?>">
     <input type="hidden" name="get_num" value="<?php echo $get_id ?>"></p>
     <input type="submit" name="submit">

     <h4>【　削除番号指定用フォーム　】</h4>
     <p><input type="text" name="delete_num" placeholder="DeleteNumber" value="<?php echo $delete_id ?>"></p>
     <p><input type="password" placeholder= "password" name="delete_pass">
     <input type="submit" name="delete_submit" value="削除"></p>

    <h4>【　編集番号指定用フォーム　】</h4>
    <p><input type="text" name="edit_num" placeholder="EditNumber" value="<?php echo $edit_id ?>"></p>
     <p><input type="password" placeholder="password" name="edit_pass">
     <input type="submit" name="edit_submit" value="編集"></p>
   </form>
 </body>
 </html>