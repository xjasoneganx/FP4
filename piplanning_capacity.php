<?php

  $nav_selected = "PIPLANNING";
  $left_buttons = "YES"; 
  $left_selected = "CALCULATE";

  include("./nav.php");
  global $db;

  date_default_timezone_set('America/Chicago');

  $sql = "SELECT program_increment, iteration, sequence
          FROM `cadence`
          WHERE start_date <= '" . date("Y-m-d") . "'
          AND end_date >= '". date("Y-m-d") . "';";
  $result = $db->query($sql);
  if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $program_increment = $row["program_increment"];
    $iteration = $row["iteration"];
    $sequence = $row["sequence"];
    $result->close();
  } else {
    //echo "In-between Iterations";
    $result->close();

    $sql = "SELECT *
        FROM
        (	SELECT MIN(start_date) as start_date, MAX(end_date) as end_date
          FROM cadence
          WHERE start_date <= '" . date("Y-m-d") . "'
          OR end_date >= '" . date("Y-m-d") . "'
          GROUP BY program_increment
        ) as PI
        WHERE PI.start_date <= '" . date("Y-m-d") . "'
        AND PI.end_date >= '" . date("Y-m-d") . "';";
    $result = $db->query($sql);
    if ($result->num_rows > 0) {
      $row = $result->fetch_assoc();
      $start_date = $row["start_date"];
      $end_date = $row["end_date"];
    } else {
      //echo "In-between Program Increments";
    }
    $result->close();
  }

  if (isset($_POST['current-sequence'])) {
    $sequence = $_POST['current-sequence'];

  }

  if (isset($_POST['current-team-selected'])) {
      $selected_team = $_POST['current-team-selected'];

  }

  if (isset($_POST['showNext'])) {
    $sequence++;
    $sql = "SELECT program_increment, iteration, sequence
            FROM `cadence`
            WHERE sequence='".$sequence."';";
    $result = $db->query($sql);
    if ($result->num_rows > 0) {
      $row = $result->fetch_assoc();
      $program_increment = $row["program_increment"];
      $iteration = $row["iteration"];
      $sequence = $row["sequence"];
      $result->close();
    } else {
      $sql = "SELECT program_increment, iteration, sequence
              FROM `cadence`
              WHERE sequence='1';";
      $result = $db->query($sql);
      if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $program_increment = $row["program_increment"];
        $iteration = $row["iteration"];
        $sequence = $row["sequence"];
        $result->close();
    }
  }
    $sql = "SELECT * FROM `capacity` where team_id='".$selected_team."' AND program_increment='".$program_increment."';";
    $result = $db->query($sql);
    if ($result->num_rows > 0) {
    } else {
      $default_data = true;
      $default_total = 0;

      $sql = "SELECT * FROM `membership` where team_id='".$selected_team."';";
      $result = $db->query($sql);
      if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {

          if ($row["role"] == "Scrum Master (SM)") {
            $velType = "SCRUM_MASTER_ALLOCATION";
          } else if ($row["role"] == "Product Owner (PO)") {
            $velType = "PRODUCT_OWNER_ALLOCATION";
          } else  {
            $velType = "AGILE_TEAM_MEMBER_ALLOCATION";
          }

          $sql2 = "SELECT * FROM `preferences` WHERE name='".$velType."';";
          $result2 = $db->query($sql2);

          if ($result2->num_rows > 0) {

              $row2 = $result2->fetch_assoc();
              $default_total += $row2["value"];

          }

      }
    }
    }
  }

  if (isset($_POST['select-team'])) {
    $selected_team = $_POST['select-team'];

    $sql = "SELECT * FROM `capacity` where team_id='".$selected_team."' AND program_increment='".$program_increment."';";
    $result = $db->query($sql);
    if ($result->num_rows > 0) {
    } else {
      $default_data = true;
      $default_total = 0;

      $sql = "SELECT * FROM `membership` where team_id='".$selected_team."';";
      $result = $db->query($sql);
      if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {

          if ($row["role"] == "Scrum Master (SM)") {
            $velType = "SCRUM_MASTER_ALLOCATION";
          } else if ($row["role"] == "Product Owner (PO)") {
            $velType = "PRODUCT_OWNER_ALLOCATION";
          } else  {
            $velType = "AGILE_TEAM_MEMBER_ALLOCATION";
          }

          $sql2 = "SELECT * FROM `preferences` WHERE name='".$velType."';";
          $result2 = $db->query($sql2);

          if ($result2->num_rows > 0) {

              $row2 = $result2->fetch_assoc();
              $default_total += $row2["value"];

          }

      }
    }
    }

  }
  if (!isset($_POST['select-team']) && !isset($_POST['current-team-selected'])) {
    $sql = "SELECT team_id FROM `capacity` where program_increment='".$program_increment."' LIMIT 1;";
    $result = $db->query($sql);

    if ($result->num_rows > 0) {

        $row = $result->fetch_assoc();
        $selected_team = $row["team_id"];
    }
  }

  $sql5 = "SELECT * FROM `cadence` WHERE program_increment='".$program_increment."';";
  $result5 = $db->query($sql5);
  if ($result5->num_rows > 0) {
      $row5 = $result5->fetch_assoc();
      $duration = $row5["duration"];
  }
  $sql6 = "SELECT * FROM `preferences` WHERE name='OVERHEAD_PERCENTAGE';";
  $result6 = $db->query($sql6);
  if ($result6->num_rows > 0) {
      $row6 = $result6->fetch_assoc();
      $overhead_percentage = $row6["value"];
  }

  if (isset($_POST['submit0'])) {
    $iterationcapacity = 0;
    for ($x=0; $x < count($_POST['rownum']); $x++){
      $teamcapacity[$_POST['rownum'][$x]] = round(($duration-$_POST['daysoff'][$x])*((100-$overhead_percentage)/100)*($_POST['velocity'][$x]/100));
      $iterationcapacity += $teamcapacity[$_POST['rownum'][$x]];
      $daysoff[$_POST['rownum'][$x]] = $_POST['daysoff'][$x];
      $velocity[$_POST['rownum'][$x]] = $_POST['velocity'][$x];
    }
    $sqliter = "UPDATE `capacity` SET iteration_".substr($iteration, -1)."='".$iterationcapacity."' WHERE program_increment='".$program_increment."' AND team_id='".$selected_team."';";
    $result_iter = $db->query($sqliter);
    $sqlinc = "SELECT (iteration_1 + iteration_2 + iteration_3 + iteration_4 + iteration_5 + iteration_6) as new_total FROM `capacity` WHERE program_increment='".$program_increment."' AND team_id='".$selected_team."';";
    $result_inc = $db->query($sqlinc);
    if ($result_inc->num_rows > 0) {
        $rowinc = $result_inc->fetch_assoc();
        $pi_capacity = $rowinc["new_total"];
      }
    $sqlup = "UPDATE `capacity` SET total='$pi_capacity' WHERE program_increment='".$program_increment."' AND team_id='".$selected_team."';";
    $result_up = $db->query($sqlup);

    // keep velocity and days off value changes
    $iterationcapacity = 0;
    for ($x=0; $x < count($_POST['rownum']); $x++){
      $teamcapacity[$_POST['rownum'][$x]] = round(($duration-$_POST['daysoff'][$x])*((100-$overhead_percentage)/100)*($_POST['velocity'][$x]/100));
      $iterationcapacity += $teamcapacity[$_POST['rownum'][$x]];
      $daysoff[$_POST['rownum'][$x]] = $_POST['daysoff'][$x];
      $velocity[$_POST['rownum'][$x]] = $_POST['velocity'][$x];
    }
  }

  ?>

<div class="right-content">
    <div class="container">

      <h3 style=" color: #01B0F1; font-weight: bold;">Capacity Calculations for the Agile Team</h3>

      <table width="95%">
        <tr>
          <td width="25%" style="vertical-align: top; font-weight: bold; color: #01B0F1; line-height: 130%; font-size: 18px;">
            <form method="post" action="#">
            Team: &emsp; <br/>
            Program Increment (PI): &emsp; <br/>
            Iteration (I): &emsp; <br/>
            No. of Days in the Iteration: &emsp; <br/>
            Overhead Percentage: &emsp; <br/>
          </td>
          <td  style="vertical-align: top; font-weight: bold; line-height: 130%;  font-size: 18px;" width="25%">
            <select name="select-team" onchange="this.form.submit()" style="border: 0; text-align: left;">
              <?php
              //$sql = "SELECT team_id, team_name FROM `capacity` where program_increment='".$program_increment."';";
              $sql = "SELECT team_id, name FROM `trains_and_teams` where type='AT';";
              $result = $db->query($sql);

              if ($result->num_rows > 0) {

                  while ($row = $result->fetch_assoc()) {
                    if ( trim($selected_team) == trim($row["team_id"]) ) {
                      echo '<option value="'.$row["team_id"].'" selected>('.$row["team_id"].': '.$row["name"].')</option>';
                    }else{
                      echo '<option value="'.$row["team_id"].'">('.$row["team_id"].': '.$row["name"].')</option>';
                    }

                  }
              }
              ?>
            </select>
          </form><br/>
          <?php
            echo "&nbsp;".$program_increment."<br/>";
            echo "&nbsp;".$iteration."<br/>";
            echo "&nbsp;".$duration."<br/>";
            echo "&nbsp;".$overhead_percentage."%<br/>";
          ?>
          </td>
          <td width="50%"  style="font-weight: bold;">
            <?php
            $sql = "SELECT * FROM `capacity` WHERE program_increment='".$program_increment."' AND team_id='".$selected_team."'";
            $result = $db->query($sql);

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();

                if (isset($teamcapacity)  && !isset($_POST['restore'])  && !isset($_POST['submit0'])){
                  $icapacity = array_sum($teamcapacity);
                  $totalcapacity = $row["total"] + ($icapacity - $row["iteration_".substr($iteration, -1)]);
                }else{
                  $icapacity = $row["iteration_".substr($iteration, -1)];
                  $totalcapacity = $row["total"];
                }

            } else {
              if (isset($teamcapacity)  && !isset($_POST['restore'])  && !isset($_POST['submit0'])){
                $icapacity = array_sum($teamcapacity);
                $totalcapacity = ($default_total*6) + ($icapacity - $default_total);
              }else{
                $icapacity = $default_total;
                $totalcapacity = $default_total*6;
              }

            }
             ?>
             <div style="float: right; margin-right: 10px; text-align: center; font-size: 12px;">
               <div id="capacity-calc-bignum" name="totalcap"><?php echo $totalcapacity ?></div>
               Total Capacity for the Program Increment
             </div>
            <div style="float: right; margin-right: 10px; text-align: center; font-size: 12px;">
              <div id="capacity-calc-bignum" name="icap"><?php echo $icapacity ?></div>
              Total Capacity for this Iteration
            </div>
          </td>
        </tr>
        <tr>
          <td colspan="3">

        <form method="post" action="#" id="maincap">
      <table id="info" cellpadding="2px" cellspacing="0" border="0" class="capacity-table"
             width="100%" style="width: 100%; clear: both; font-size: 15px; margin: 8px 0 15px 0">

          <thead>

          <tr id="capacity-table-first-row">

              <th id="capacity-table-td">Last Name</th>
              <th id="capacity-table-td">First Name</th>
              <th id="capacity-table-td">Role</th>
              <th id="capacity-table-td">% Velocity Available</th>
              <th id="capacity-table-td">Days Off <br/><p style="font-size: 9px;">(Vacation / Holidays / Sick Days)</p></th>
              <th id="capacity-table-td">Story Points</th>

          </tr>

          </thead>

          <tbody>


          <?php

          $sql = "SELECT last_name, first_name, role FROM `membership`
                  NATURAL JOIN `employees`
                  WHERE team_id='".$selected_team."';";

          $result = $db->query($sql);


          if ($result->num_rows > 0) {

              // output data of each
              $rownum = 0;
              while ($row = $result->fetch_assoc()) {

                if ($row["role"] == "Scrum Master (SM)") {
                  $velocityType = "SCRUM_MASTER_ALLOCATION";
                } else if ($row["role"] == "Product Owner (PO)") {
                  $velocityType = "PRODUCT_OWNER_ALLOCATION";
                } else  {
                  $velocityType = "AGILE_TEAM_MEMBER_ALLOCATION";
                }

                $sql2 = "SELECT * FROM `preferences` WHERE name='".$velocityType."';";
                $result2 = $db->query($sql2);

                if ($result2->num_rows > 0) {

                    $row2 = $result2->fetch_assoc();

                }
                if (isset($teamcapacity[$rownum]) && !isset($_POST['restore']) && isset($_POST['submit0'])){
                  $storypts = $teamcapacity[$rownum];
                }else{
                  $storypts = round(($duration-0)*((100-$overhead_percentage)/100)*($row2["value"]/100));
                }
                $valueForJS = $row2["value"];
                if (isset($daysoff[$rownum]) && !isset($_POST['restore'])  && isset($_POST['submit0'])){
                  $doff = $daysoff[$rownum];
                } else {
                  $doff = 0;
                }
                if (isset($velocity[$rownum]) && !isset($_POST['restore']) && isset($_POST['submit0'])){
                  $vel = $velocity[$rownum];
                } else {
                  $vel = $row2["value"];
                }

                  echo
                  "<tr>
                      <td id='capacity-table-td' style='font-weight:500;'>" . $row["last_name"] . "</td>
                      <td id='capacity-table-td' style='font-weight:500;'>" . $row["first_name"] . "</td>
                      <td id='capacity-table-td' style='font-weight:500;'>" . $row["role"] . "</td>
                      <td id='capacity-table-td' style='font-weight:500; text-align: center;'><input id='autoin' class='capacity-text-input' type='text' name='velocity[]' value='" . $vel . "' onchange='autoLoad();' /> %</td>
                      <td id='capacity-table-td' style='font-weight:500; text-align: center;'><input id='autoin2' class='capacity-text-input' type='text' name='daysoff[]' value='".$doff."' onchange='autoLoad();' /></td>
                      <td id='capacity-table-td' style='font-weight:500; text-align: center;  background: #e9e9e9;'><input id='story' class='capacity-text-input' type='text' name='storypoints[]' value='".$storypts."' readonly='readonly' style='border: 0;  background: #e9e9e9;' />&nbsp;pts</td>
                      <input type='hidden' name='rownum[]' value='".$rownum."'/>
                  </tr>";
                  $rownum++;
              }
          } else {
            echo "<tr><td colspan='6' id='capacity-table-td'  style='text-align: center; font-weight: bold; padding: 20px 0 20px 0'>";
              print "NO TEAM MEMBERS ASSIGNED TO TEAM \"".$selected_team."\"";
              echo "</td></tr>";
          }

          $result->close();
          ?>

          </tbody>

          <tfoot>

          </tfoot>

      </table>
      <input type="submit" id="capacity-button-blue" name="submit0" value="Submit">
      <input type="submit" id="capacity-button-blue" name="restore" value="Restore Defaults">
      <input type="submit" id="capacity-button-blue" name="showNext" value="Show Next Iteration">
        <input type="hidden" name="current-team-selected" value="<?php echo $selected_team; ?>">
        <input type="hidden" name="current-sequence" value="<?php echo $sequence; ?>">
      </form>

      <div id="capacity-footnote">
        Note 1: Closed Iterations will NOT be shown.  The capacity cannot be changed for such iterations.  Show only the active iterations.<br/>
        Note 2: This page can be reached in two ways:
        <ul>
          <li>Capacity > Calculate</li>
          <li>Capacity > Summary > By clicking on one of the numbers</li>
        </ul>
      </div>

      </td>
      </tr>
      </table>

    </div>
    </div>

    <script type="text/javascript">

        $(document).ready(function () {

            $('#info').DataTable({
                paging: false,
                searching: false,
                infoCallback: false
            });

        });

        function autoForm() {
          document.getElementById('maincap').submit();
        }

        function autoLoad() {
          var velocity = $("input[name='velocity[]']")
              .map(function(){return $(this).val();}).get();
          var daysoff = $("input[name='daysoff[]']")
              .map(function(){return $(this).val();}).get();
          var rownum = $("input[name='rownum[]']")
              .map(function(){return $(this).val();}).get();

          var overhead = "<?php echo $overhead_percentage ?>";
          var duration = "<?php echo $duration ?>";
          var value = "<?php echo $valueForJS ?>";
          var totalcap_old = "<?php echo $totalcapacity ?>";
          var icap_old = "<?php echo $icapacity ?>";
          var icap = 0;

          for (var i in rownum) {
              var storypts = Math.round( ( duration - daysoff[i] ) * ( ( 100-overhead ) / 100 ) * ( velocity[i] / 100 ) );
              $("input[name='storypoints[]']").eq(i).val(storypts);
              icap += storypts;
          }

          document.getElementsByName("icap")[0].innerHTML = icap;
          var capdiff = icap - icap_old;
          var tcap = parseInt(capdiff) + parseInt(totalcap_old);
          document.getElementsByName("totalcap")[0].innerHTML = tcap;
        }


    </script>

<?php include("./footer.php"); ?>
